<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Manages gateway operations
 * @property  Currency_model Currency_model
 */
class ProcessPayment
{

    const DEFAULT_CURRENCY = 'usd';

    /**
     * @var CI_Controller
     */
    private $ci;

    private $selected_gateway;

    /**
     * @var array Company gateway settings
     */
    private $company_gateway_settings;

    /**
     * @var array Customer
     */
    private $customer;

    /**
     * @var string Error message
     */
    private $error_message;

    /**
     * @var string
     */
    private $currency = self::DEFAULT_CURRENCY;

    /**
     *
     * @var string External Id, can only be one per gateway
     */
    private $customer_external_entity_id;

    public $api_key, $tokenex_id, $api_tokenization_url, $api_payment_url, $api_iframe_url, $api_transparent_url;

    function __construct($params = null)
    {   
        $this->ci =& get_instance();
        $this->ci->load->model('Payment_gateway_model');
        $this->ci->load->model('Customer_model');
        $this->ci->load->library('session');
        $this->ci->load->model("Card_model");

        $this->ci->load->library('encrypt');
        $this->ci->load->model('Booking_model');
        $this->ci->load->model('company_model');          
        
        $company_id = $this->ci->session->userdata('current_company_id');

        if (isset($params['company_id'])) {
            $company_id = $params['company_id'];
        }

        $gateway_settings = $this->ci->Payment_gateway_model->get_payment_gateway_settings(
            $company_id
        );
                    
                if($gateway_settings)
                {
                    $this->setCompanyGatewaySettings($gateway_settings);
                    $this->setSelectedGateway($this->company_gateway_settings['selected_payment_gateway']);
                    $this->populateGatewaySettings();
                    $this->setCurrency();       
                }       
    }

    private function populateGatewaySettings()
    {
        switch ($this->selected_gateway) {
            case 'stripe':
                $this->stripe_private_key = $this->company_gateway_settings['stripe_secret_key'];
                $this->stripe_public_key  = $this->company_gateway_settings['stripe_publishable_key'];
                \Stripe\Stripe::setApiKey($this->stripe_private_key);
                break;
        }
    }

    private function setCurrency()
    {
        // itodo some gateway currency maybe unavailable
        $this->ci->load->model('Currency_model');
        $currency       = $this->ci->Currency_model->get_default_currency($this->company_gateway_settings['company_id']);
        $this->currency = strtolower($currency['currency_code']);
    }

    public static function getGatewayNames()
    {
        return array(
            'stripe' => 'Stripe'
        );
    }

    /**
     * @return int
     */
    public function getStripeToken()
    {
        return $this->stripe_token;
    }

    /**
     * @param $token
     */
    public function setCCToken($token)
    {
        switch ($this->selected_gateway) {
            case 'stripe':
                $this->setStripeToken($token);
                break;
        }
    }

    /**
     * @param int $stripe_token
     */
    private function setStripeToken($stripe_token)
    {
        $this->stripe_token = $stripe_token;
    }

    /**
     * @return string
     */
    public function getStripePrivateKey()
    {
        return $this->stripe_private_key;
    }

    /**
     * @param string $stripe_private_key
     */
    public function setStripePrivateKey($stripe_private_key)
    {
        $this->stripe_private_key = $stripe_private_key;
    }

    /**
     * @return string
     */
    public function getStripePublicKey()
    {
        return $this->stripe_public_key;
    }

    /**
     * @param string $stripe_public_key
     */
    public function setStripePublicKey($stripe_public_key)
    {
        $this->stripe_public_key = $stripe_public_key;
    }

    /**
     * @return string
     */
    public function getSelectedGateway()
    {
        return $this->selected_gateway;
    }

    /**
     * @param string $selected_gateway
     */
    public function setSelectedGateway($selected_gateway)
    {
        $this->selected_gateway = $selected_gateway;
    }

    public function createExternalEntity()
    {
        $external_id = null;

        switch ($this->selected_gateway) {
            case 'stripe':
                $data        = \Stripe\Customer::create(
                    array(
                        "description" => json_encode(
                            array(
                                'customer_id' => isset($this->customer['customer_id']) ? $this->customer['customer_id'] : 'new_customer',
                            )
                        ),
                        "source"      => $this->stripe_token
                    )
                );
                $external_id = $data->id;
                break;
        }

        return $external_id;
    }

    /**
     * @return mixed
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @param $customer_id
     */
    public function setCustomerById($customer_id)
    {
        $customer = $this->ci->Customer_model->get_customer($customer_id);
        unset($customer['cc_number']);
        unset($customer['cc_expiry_month']);
        unset($customer['cc_expiry_year']);
        unset($customer['cc_tokenex_token']);
        unset($customer['cc_cvc_encrypted']);
        
        $card_data = $this->ci->Card_model->get_active_card($customer_id, $this->ci->company_id);
            
        if($card_data){
            $customer['cc_number'] = $card_data['cc_number'];
            $customer['cc_expiry_month'] = $card_data['cc_expiry_month'];
            $customer['cc_expiry_year'] = $card_data['cc_expiry_year'];
            $customer['cc_tokenex_token'] = $card_data['cc_tokenex_token'];
            $customer['cc_cvc_encrypted'] = $card_data['cc_cvc_encrypted'];
        }
            
        $this->customer = json_decode(json_encode($customer), 1);
    }

    public function getCustomerExternalEntityId()
    {
        $id = null;

        if ($this->customer) {
            switch ($this->selected_gateway) {
                case 'stripe':
                    $id = $this->customer['stripe_customer_id'];
                    break;
            }
        }


        return $id;
    }

   
    /**
     * @param string $customer_external_entity_id
     */
    public function setCustomerExternalEntityId($customer_external_entity_id)
    {
        $this->customer_external_entity_id = $customer_external_entity_id;
    }

    /**
     * @param $booking_id
     * @param $amount
     * @return bool
     */
    public function createBookingCharge($booking_id, $amount, $customer_id = null, $cvc = null, $is_capture = true)
    {
        $charge_id = null;

        if ($this->isGatewayPaymentAvailableForBooking($booking_id, $customer_id)) {
            try {
                $this->ci->load->model('Booking_model');
                $this->ci->load->model('Customer_model');
                $this->ci->load->model('Card_model');
                $this->ci->load->library('tokenex');
                
                $booking     = $this->ci->Booking_model->get_booking($booking_id);
                
                $customer_id = $customer_id ? $customer_id : $booking['booking_customer_id'];
                
                $customer_info    = $this->ci->Card_model->get_customer_cards($customer_id);
                //print_r($customer);
                $customer = "";
                if(isset($customer_info) && $customer_info){
                    
                    foreach($customer_info as $customer_data){
                        if(($customer_data['is_primary']) && !$customer_data['is_card_deleted']){
                            $customer = $customer_data;
                        }
                    } 
                }
                
                $customer    = json_decode(json_encode($customer), 1);
               
                if(isset($customer['cc_tokenex_token']) && $customer['cc_tokenex_token'])
                {
                    $stripe_secret_key = $this->stripe_private_key;
                    // use tokenex for payments
                    $charge = $this->make_payment($stripe_secret_key, $amount, $this->currency,$customer);

                    $charge_id = null;
                    if($charge['success'])
                    {
                        if(isset($charge['charge_id']) && $charge['charge_id'])
                            $charge_id = $charge['charge_id'];
                        else
                        {
                           return $charge['authorization'];
                        }
                    }
                    else
                    {
                        $charge_id = isset($charge['charge_failed']) && $charge['charge_failed'] ? $charge['charge_failed'].'-charge_failed' : (isset($charge['message']) && $charge['message'] ? $charge['message'].'-charge_failed' : '');
                        $this->setErrorMessage($charge['message']);
                    }
                }
                
                              
            } catch (Exception $e) {
                $error = $e->getMessage();
                $this->setErrorMessage($error);
            }
        }

        return $charge_id;
    }

    /**
     * Can Booking perform payment operations
     *
     * @param $booking_id
     * @return bool
     */
    public function isGatewayPaymentAvailableForBooking($booking_id, $customer_id = null)
    {
        $result = false;

        $this->ci->load->model('Booking_model');
        $this->ci->load->model('Customer_model');

        $booking       = $this->ci->Booking_model->get_booking($booking_id);
        
        $customer_id = $customer_id ? $customer_id : $booking['booking_customer_id'];
        
        $customer      = $this->ci->Customer_model->get_customer($customer_id);
        
        unset($customer['cc_number']);
        unset($customer['cc_expiry_month']);
        unset($customer['cc_expiry_year']);
        unset($customer['cc_tokenex_token']);
        unset($customer['cc_cvc_encrypted']);
        
        $card_data = $this->ci->Card_model->get_active_card($customer_id, $this->ci->company_id);
            
        if(isset($card_data) && $card_data){
            $customer['cc_number'] = $card_data['cc_number'];
            $customer['cc_expiry_month'] = $card_data['cc_expiry_month'];
            $customer['cc_expiry_year'] = $card_data['cc_expiry_year'];
            $customer['cc_tokenex_token'] = $card_data['cc_tokenex_token'];
            $customer['cc_cvc_encrypted'] = $card_data['cc_cvc_encrypted'];
        }
            
        $customer      = json_decode(json_encode($customer), 1);
        $hasExternalId = (isset($customer[$this->getExternalEntityField()]) and $customer[$this->getExternalEntityField()]);
        $hasTokenexToken = (isset($customer['cc_tokenex_token']) and $customer['cc_tokenex_token']);
        
        if(!$hasTokenexToken)
        {
            $customer      = $this->ci->Customer_model->get_customer($customer_id);
            $customer      = json_decode(json_encode($customer), 1);
            $hasExternalId = (isset($customer[$this->getExternalEntityField()]) and $customer[$this->getExternalEntityField()]);
            $hasTokenexToken = (isset($customer['cc_tokenex_token']) and $customer['cc_tokenex_token']);
        }
        
        if (
            $this->areGatewayCredentialsFilled()
            and $customer
            and ($hasExternalId or $hasTokenexToken)
        ) {
            $result = true;
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getExternalEntityField()
    {
        $name = '';
        switch ($this->selected_gateway) {
            case 'stripe':
                $name = 'stripe_customer_id';
                break;
        }

        return $name;
    }

    /**
     * Checks if gateway settings are filled
     *
     * @return bool
     */
    public function areGatewayCredentialsFilled()
    {
        $filled                       = true;
        $selected_gateway_credentials = $this->getSelectedGatewayCredentials();

        foreach ($selected_gateway_credentials as $credential) {
            if (empty($credential)) {
                $filled = false;
            }
        }

        return $filled;
    }

    /**
     * @param bool $publicOnly
     * @return array
     */
    public function getSelectedGatewayCredentials($publicOnly = false)
    {
        $credentials = $this->getGatewayCredentials($this->selected_gateway, $publicOnly);

        return $credentials;
    }

    /**
     * @param null $filter
     * @param bool $publicOnly
     * @return array
     */
    public function getGatewayCredentials($filter = null, $publicOnly = false)
    {
        $credentials                                     = array();
        $credentials['selected_payment_gateway']         = $this->selected_gateway; // itodo legacy
        $credentials['stripe']['stripe_publishable_key'] = $this->company_gateway_settings['stripe_publishable_key'];

        if (!$publicOnly) {
            $credentials['stripe']['stripe_secret_key'] = $this->company_gateway_settings['stripe_secret_key'];
        }

        $result                                = $credentials;

        if ($filter) {
            $result                             = isset($result[$filter]) ? $result[$filter] : $result['payment_gateway'];
            $result['selected_payment_gateway'] = $this->selected_gateway; // itodo legacy
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->error_message;
    }

    /**
     * @param string $error_message
     */
    public function setErrorMessage($error_message)
    {
        $this->error_message = $error_message;
    }

    /**
     * @param $payment_id
     */
    public function refundBookingPayment($payment_id, $amount, $payment_type, $booking_id = null)
    {
        $result = array("success" => true, "refund_id" => true);
        $this->ci->load->model('Payment_model');
        $this->ci->load->model('Customer_model');
        $this->ci->load->library('tokenex');
   
        $payment = $this->ci->Payment_model->get_payment($payment_id);
        
        try {
            if ($payment['payment_gateway_used'] and $payment['gateway_charge_id']) {
                $customer    = $this->ci->Customer_model->get_customer($payment['customer_id']);
                
                unset($customer['cc_number']);
                unset($customer['cc_expiry_month']);
                unset($customer['cc_expiry_year']);
                unset($customer['cc_tokenex_token']);
                unset($customer['cc_cvc_encrypted']);

                $card_data = $this->ci->Card_model->get_active_card($payment['customer_id'], $this->ci->company_id);

                if(isset($card_data) && $card_data){
                    $customer['cc_number'] = $card_data['cc_number'];
                    $customer['cc_expiry_month'] = $card_data['cc_expiry_month'];
                    $customer['cc_expiry_year'] = $card_data['cc_expiry_year'];
                    $customer['cc_tokenex_token'] = $card_data['cc_tokenex_token'];
                    $customer['cc_cvc_encrypted'] = $card_data['cc_cvc_encrypted'];
                }
                
                $customer    = json_decode(json_encode($customer), 1);
                if(isset($customer['cc_tokenex_token']) && $customer['cc_tokenex_token'])
                {
                    if($payment_type == 'full'){
                        $amount = abs($payment['amount']) * 100; // in cents, only positive
                    }

                    $stripe_secret_key = $this->stripe_private_key;
                    $result = $this->refund_payment($stripe_secret_key, $amount, $payment['gateway_charge_id']);
                    
                    // $result = $this->refund_payment($this->selected_gateway, $payment['gateway_charge_id'], $amount, $this->currency, $booking_id, $payment['credit_card_id']);
                    
                }
                
            }
        } catch (Exception $e) {
            $result = array("success" => false, "message" => $e->getMessage());
        }

        return $result;
    }

    
    public function getCustomerTokenInfo()
    {
        $data = array();
        foreach ($this->getPaymentGateways() as $gateway => $settings) {
            if (isset($this->customer[$settings['customer_token_field']]) and $this->customer[$settings['customer_token_field']]) {
                $data[$gateway] = $this->customer[$settings['customer_token_field']];
            }
        }
        return $data;
    }

    /**
     * @return array
     */
    public static function getPaymentGateways()
    {
        return array(
            'stripe' => array(
                'name'                 => 'Stripe',
                'customer_token_field' => 'stripe_customer_id'
            )
        );
    }
   
    /**
     * @param $payment_type
     * @param $company_id
     * @return array
     */
    public function getPaymentGatewayPaymentType($payment_type, $company_id = null)
    {
        $settings   = $this->getCompanyGatewaySettings();
        $company_id = $company_id ?: $settings['company_id'];

        $row = $this->query("select * from payment_type WHERE payment_type = '$payment_type' and company_id = '$company_id'");

        if (empty($row)) {
            // if doesn't exist - create
            $this->createPaymentGatewayPaymentType($payment_type, $company_id);
            $result = $this->getPaymentGatewayPaymentType($payment_type, $company_id);
        } else {
            $result = reset($row);
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getCompanyGatewaySettings()
    {
        return $this->company_gateway_settings;
    }

    /**
     * @param array $company_gateway_settings
     */
    public function setCompanyGatewaySettings($company_gateway_settings)
    {
        $this->company_gateway_settings = $company_gateway_settings;
    }

    private function query($sql)
    {
        return $this->ci->db->query($sql)->result_array();
    }

    /**
     * @param $company_id
     */
    public function createPaymentGatewayPaymentType($payment_type, $company_id)
    {
        $this->ci->db->insert(
            'payment_type',
            array(
                'payment_type' => $payment_type,
                'company_id'   => $company_id,
                'is_read_only' => '1'
            )
        );

        return $this->ci->db->insert_id();
    }
    
    public function create_customer_id($token){

        $stripe_secret_key = $this->stripe_private_key;

        $cust_data = array();

        $stripe = new Stripe\StripeClient($stripe_secret_key);

        $description = 'Minical-stripe-customer-id'.strtotime(date('Y-m-d H:i:s'));
        
        $customer_response = $stripe->customers->create([
            'description' => $description,
            'card'  => $token
        ]);

        $customer_response = json_decode(json_encode($customer_response, true), true);

        return array('success' => true, 'customer_id' => $customer_response['id']);

    }

    public function create_token($cvc, $cc_number, $cc_expiry_month, $cc_expiry_year){

        $stripe_secret_key = $this->stripe_private_key;

        $stripe = new Stripe\StripeClient($stripe_secret_key);

        $stripe_token_resp = $stripe->tokens->create([
            'card' => [
                'number' => $cc_number,
                'exp_month' => $cc_expiry_month,
                'exp_year' => $cc_expiry_year,
                'cvc' => $cvc,
            ],
        ]);

        $stripe_token_response = json_decode(json_encode($stripe_token_resp, true), true);

        return array('success' => true, 'token' => $stripe_token_response['id'], 'cc_last_digits' => $stripe_token_response['card']['last4']);

    }

    public function make_payment($stripe_secret_key, $amount, $currency, $customer){

        $stripe = new Stripe\StripeClient($stripe_secret_key);

        $description = 'Minical-booking-payment'.strtotime(date('Y-m-d H:i:s'));
        
        $stripe_charge = $stripe->charges->create([
          'amount' => $amount,
          'currency' => $currency,
          'customer' => $customer['stripe_customer_id'],
          'description' => $description
        ]);

        $stripe_charge = json_decode(json_encode($stripe_charge, true), true);

        return array('success' => true, 'charge_id' => $stripe_charge['id']);
    }

    public function refund_payment($stripe_secret_key, $amount, $gateway_charge_id){

        $stripe = new Stripe\StripeClient($stripe_secret_key);

        $refund_charge = $stripe->refunds->create([
            'charge' => $gateway_charge_id,
            'amount' => $amount
        ]);

        $refund_charge = json_decode(json_encode($refund_charge, true), true);

        return array('success' => true, 'refund_id' => $refund_charge['id']);
    }
}