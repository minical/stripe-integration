<?php
class Integrations extends MY_Controller
{
     public $module_name;
    function __construct()
	{
        parent::__construct();
        $this->module_name = $this->router->fetch_module();

        $this->load->model('../extensions/'.$this->module_name.'/models/Payment_gateway_model');
        $this->load->model('../extensions/'.$this->module_name.'/models/Employee_log_model');
        $this->load->model('../extensions/'.$this->module_name.'/models/Bookings_model');
        $this->load->model('../extensions/'.$this->module_name.'/models/Card_model');
        $this->load->model('../extensions/'.$this->module_name.'/models/Customer_model');
        
        $this->load->model('Payment_model');
        $this->load->model('Invoice_log_model');

        $this->load->library('PaymentGateway');
        $this->load->library('../extensions/'.$this->module_name.'/libraries/ProcessPayment');
		
        $view_data['menu_on'] = true;       
		$this->load->vars($view_data);
	}
    
    function stripe_payment_gateway()
    {       
        $data['selected_sidebar_link'] = 'Payment Gateways';
        $data['main_content'] = '../extensions/'.$this->module_name.'/views/payment_gateway_settings';
        $stripeData = $this->paymentgateway->getGatewayCredentials();
        $data['stripeData']=$stripeData['stripe'];
        $this->load->view('includes/bootstrapped_template', $data);
    }

    function update_stripe_payment_gateway_settings() {
        foreach($_POST as $key => $value)
        {
            if(
                $key != 'gateway_mid' &&
                $key != 'gateway_tid' && 
                $key != 'gateway_cid' && 
                $key != 'gateway_public_key' && 
                $key != 'gateway_private_key' && 
                $key != 'gateway_app_id' && 
                $key != 'gateway_user' && 
                $key != 'gateway_merchant_id' && 
                $key != 'gateway_merchant_key' &&
                $key != 'gateway_square_app_id' &&
                $key != 'gateway_square_access_token'

            )
            {
                $data[$key] = $this->input->post($key);
            }
        }
        
        $data['company_id'] = $this->company_id;
        $this->Payment_gateway_model->update_payment_gateway_settings($data);
        $this->_create_accounting_log("Update Payment Gateway Setting");

        if($data['selected_payment_gateway'] == 'QuickbooksGateway')
        {
            $data['authorizationCodeUrl'] = json_encode($authorizationCodeUrl);
        }
        echo json_encode($data);
    }

    function get_stripe_payment_gateway_settings() {
        $data = $this->paymentgateway->getGatewayCredentials();
        echo json_encode($data);
    }

    function deconfigure_stripe_apikey(){

        $this->Card_model->deconfigure_stripe_apikey($this->company_id);
           
    }
    
    function _create_accounting_log($log) {
        $log_detail =  array(
                    "user_id" => $this->user_id,
                    "selling_date" => $this->selling_date,
                    "date_time" => gmdate('Y-m-d H:i:s'),
                    "log" => $log,
                );   
        
        $this->Employee_log_model->insert_log($log_detail);     
    }

    function get_customer_card_data()
    {
        $booking_id = sqli_clean($this->security->xss_clean($this->input->post('booking_id')));
        
        $cus_assay = array();
        $booking_data = $this->Bookings_model->get_booking($booking_id);
        $customer = $this->Card_model->get_customer_card_detail($booking_data['booking_customer_id']);
        $cus_assay[] = $customer ;
        $staying_customers = $this->Customer_model->get_staying_customers($booking_id);
        if($staying_customers){
             foreach($staying_customers as $cus){
               $sta_data = $this->Card_model->get_customer_card_detail($cus['customer_id']);
               $cus_assay[] = $sta_data;
            } 
        }
           
        echo json_encode($cus_assay); 
    }

    public function update_customer_card_is_primary()
    {
        $customer_id = sqli_clean($this->security->xss_clean($this->input->post('customer_id')));
        $card_id = sqli_clean($this->security->xss_clean($this->input->post('card_id')));
        $active = sqli_clean($this->security->xss_clean($this->input->post('active')));
        
        $update_res = $this->Card_model->update_customer_card_is_primary_card_table($customer_id, $card_id, $active, $this->company_id);
    
        if($update_res){
            $res = "success";
        }else{
            $res = "fail";
        }
        echo json_encode($res);
    }

    function check_stripe_token_availability(){
        $customer_id = $this->input->post('customer_id');
        $customer_details = $this->Card_model->get_customer_cards($customer_id, true);

        $response = array('success' => false);

        $card_data = isset($customer_details) && $customer_details ? json_decode($customer_details[0]['customer_meta_data'], true) : null;

        if(
            isset($card_data['token']) &&
            $card_data['token'] &&
            strpos($card_data['token'], "tok_") !== false
        ) {
            $response = array('success' => true);
            echo json_encode($response);
        } else {
            echo json_encode($response);
        }

    }

    function add_stripe_token(){
        $customer_data = $this->input->post('data');

        $customer_id = $customer_data['customer_id'];
        $create_stripe_customer_from = "";

        if(
            isset($customer_data['create_stripe_customer_from']) &&
            $customer_data['create_stripe_customer_from'] == 'invoice_page'
        ){
            $create_stripe_customer_from = 'invoice_page';
            unset($customer_data['create_stripe_customer_from']);
        }

        // if(
        //     isset($customer_data['customer_id']) &&
        //     $customer_data['customer_id']
        // ) {
        //     $customer_id = $customer_data['customer_id'];
        $customer_details = $this->Customer_model->get_customer($customer_data['customer_id']);

        //     $customer_data = array_merge($customer_data, $customer_details);
        // }

        // // echo "customer_data = ";prx($customer_data);echo " = customer_datasss";
        // $create_customer = $this->processpayment->crateSquareCustomer($customer_data);

        if(
            $customer_data['stripe_token'] &&
            $customer_data['stripe_exp_month'] &&
            $customer_data['stripe_exp_year'] &&
            $customer_data['stripe_lastfour']
        ) {

            $data['success'] = true;
            $data['token'] = $customer_data['stripe_token'];

            if($customer_id){
                unset($data['success']);
                $data['source'] = 'stripe';

                if($create_stripe_customer_from == 'invoice_page'){
                    $card_details = array(
                       'is_primary' => 1,
                       'customer_id' => $customer_id,
                       'customer_name' => $customer_details['customer_name'],
                       'card_name' => '',
                       'company_id' => $this->company_id,
                       'cc_number' => "XXXX XXXX XXXX " . $customer_data['stripe_lastfour'],
                       'cc_expiry_month' => (isset($customer_data['stripe_exp_month']) ? $customer_data['stripe_exp_month'] : NULL),
                       'cc_expiry_year' => (isset($customer_data['stripe_exp_year']) ? $customer_data['stripe_exp_year'] : NULL)
                    );

                    $card_details['customer_meta_data'] = json_encode($data);
                }

                $existing_card_data = $this->Card_model->get_active_card($customer_id, $this->company_id);

                $card_id = $existing_card_data['id'];

                if(!empty($existing_card_data)){
                    $this->Card_model->update_customer_card_info($customer_id, $card_details, $card_id);
                } else{
                    $this->Card_model->create_customer_card_info($card_details);
                }

            } else {
                echo json_encode($card_details);
            }
        }
    }

    function add_stripe_payment(){

        $group_id = $this->input->post('group_id');
        
        if($group_id)
        {
            $selling_date = sqli_clean($this->security->xss_clean($this->input->post('payment_date')));
            $payment_type_id = sqli_clean($this->security->xss_clean($this->input->post('payment_type_id')));
            $customer_id = sqli_clean($this->security->xss_clean($this->input->post('customer_id')));
            $total_balance = sqli_clean($this->security->xss_clean($this->input->post('payment_amount')));
            $description = sqli_clean($this->security->xss_clean($this->input->post('description')));
            //$cvc = sqli_clean($this->security->xss_clean($this->input->post('cvc')));
            $distribute_equal_amount = $this->input->post('payment_distribution');
            $folio_id = sqli_clean($this->security->xss_clean($this->input->post('folio_id')));
            // $capture_payment = sqli_clean($this->security->xss_clean(trim($this->input->post('capture_payment_type'))));

            $get_bookings_by_group_id = $this->Bookings_model->get_bookings_by_group_id($group_id, true);
            // prx($get_bookings_by_group_id);
            $remaining_balance = $total_balance;
            $no_of_bookings = count($get_bookings_by_group_id);
            $equal_amount = $total_balance / $no_of_bookings;
            $round_total_amount = (round($equal_amount,2)) * $no_of_bookings;
            $amount_diff = round(($round_total_amount - $total_balance), 2);
            
            $company_data =  $this->Company_model->get_company($this->company_id);
            //$capture_payment_type = $company_data['manual_payment_capture'];
            //$capture_payment_type = ($capture_payment != 'authorize_only') ? false : true;

            // For proportional distribution
            $total_group_balance = array_sum(array_column($get_bookings_by_group_id, 'balance'));
             
            $i = 1;
            $response = array();
            foreach ($get_bookings_by_group_id as $booking)
            {
                $balance = $booking['balance'];
                
                if($distribute_equal_amount == "Yes")
                {
                    if($i == $no_of_bookings && $amount_diff != 0)
                    {
                        $amount = $equal_amount - $amount_diff;
                        $amount = round($amount, 2);
                    }
                    else
                    {
                        $amount = round($equal_amount, 2);
                    }                
                }
                elseif ($distribute_equal_amount == "Proportional") {
                    // ✅ Proportional distribution
                    if ($total_group_balance > 0) {
                        $share_ratio = $balance / $total_group_balance;
                        $amount = round($total_balance * $share_ratio, 2);

                        // prevent overpay
                        if ($amount > $balance) {
                            $amount = $balance;
                        }
                    } else {
                        $amount = 0;
                    }
                }
                else
                {
                    $amount = $balance;
                    if($remaining_balance < $balance)
                    {
                        $amount = $remaining_balance;
                    }
                }
                if($remaining_balance <= 0){
                    $response = array(
                        'success' => false,
                        'message' => 'You cannot make a payment for a negative amount on a group invoice. Please process a refund through the individual bookings instead.'
                    );
                    break;
                }
                
                $data = Array(
                    "user_id" => $this->user_id,
                    "booking_id" => $booking['booking_id'],
                    "selling_date" => $selling_date,
                    "customer_id" => $customer_id,
                    "amount" => $amount,
                    "payment_type_id" => $payment_type_id,
                    "description" => $description,
                    "date_time" => gmdate("Y-m-d H:i:s"),
                    "selected_gateway" => $this->input->post('selected_gateway'),
                );
                $card_data = $this->Card_model->get_active_card($customer_id, $this->company_id);
                $data['credit_card_id'] = "";
                if(isset($card_data) && $card_data){
                     $data['credit_card_id'] = $card_data['id'];
                }
                
                if($data['amount'] > 0)
                {
                    $response = $this->Payment_model->insert_payment($data);
                }
                else
                {
                    $response = array('success' => false, 'message' => 'All the bookings in this group are already Paid in full.');
                }
                if($response['success'] == false){
                    $response[] = array(
                                    "booking_id" => $booking['booking_id'],
                                    "error_msg" =>  $response['message']
                                );
                }
                elseif($response['success'])
                {

                    $post_payment_data = $response;
                    $post_payment_data['payment_id'] = $response['payment_id'];

                    do_action('post.create.payment', $post_payment_data);

                    $invoice_log_data = array();
                    $invoice_log_data['date_time'] = gmdate('Y-m-d h:i:s');
                    $invoice_log_data['booking_id'] = $booking['booking_id'];
                    $invoice_log_data['user_id'] = $this->session->userdata('user_id');
                    $invoice_log_data['action_id'] = $company_data['manual_payment_capture'] ? AUTHORIZED_PAYMENT : CAPTURED_PAYMENT;
                    $invoice_log_data['charge_or_payment_id'] = $response['payment_id'];
                    $invoice_log_data['new_amount'] = $amount;
                    if($invoice_log_data['charge_or_payment_id'])
                    {
                        $this->Payment_model->insert_payment_folio(array('payment_id' => $response['payment_id'], 'folio_id' => $folio_id));
                        
                        $invoice_log_data['log'] = $company_data['manual_payment_capture'] ? 'Payment Authorized' : 'Payment Captured';
                        $this->Invoice_log_model->insert_log($invoice_log_data);
                    }
                    $this->Bookings_model->update_booking_balance($booking['booking_id']);
                }
                $i++;
                $remaining_balance -= $amount; 
            }
            
            if (!empty($response)) {
                echo json_encode($response);
            }
        }

        else {

            $data = Array(
                "user_id" => $this->session->userdata('user_id'),
                "booking_id" => $this->input->post('booking_id'),
                "selling_date" => date('Y-m-d', strtotime($this->input->post('payment_date'))),
                "amount" => $this->input->post('payment_amount'),
                "customer_id" => $this->input->post('customer_id'),
                "payment_type_id" => $this->input->post('payment_type_id'),
                "description" => $this->input->post('description'),
                "date_time" => gmdate("Y-m-d H:i:s"),
                "selected_gateway" => $this->input->post('selected_gateway')
            );

            $payment_folio_id = $this->input->post('folio_id');
            $payment_folio_id = $payment_folio_id ? $payment_folio_id : 0;
            $card_data = $this->Card_model->get_active_card($data['customer_id'], $this->company_id);
            $data['credit_card_id'] = null;
            if (isset($card_data) && $card_data) {
                $data['credit_card_id'] = $card_data['id'];
            }

            $payment_type_id               = &$data['payment_type_id'];
            $use_gateway                   = ($payment_type_id == 'gateway');

            if($use_gateway){

                $payment_type    = $this->processpayment->getPaymentGatewayPaymentType($data['selected_gateway']);
                $payment_type_id = $payment_type['payment_type_id'];

                $gateway_charge_id = $this->processpayment->createBookingCharge(
                    $data['booking_id'],
                    abs($data['amount']), // in cents, only positive
                    $data['customer_id']
                );

                $error = $this->processpayment->getErrorMessage();
            }

            // if(isset($gateway_charge_id[0]) && isset($gateway_charge_id[0]['code'])){
            //     $error = $gateway_charge_id;
            // } else if($gateway_charge_id == 'Tokenization service is not available.'){
            //     $error = $gateway_charge_id;
            // }
            
            if ($use_gateway && $gateway_charge_id) {
                $data['payment_gateway_used'] = $this->processpayment->getSelectedGateway();
                $data['gateway_charge_id'] = $gateway_charge_id;
                $data['is_captured'] = 1;
                $data['description'] = isset($data['description']) && $data['description'] ? $data['description'].'<br/>' : '';

                // insert payment
            
                $data['payment_status'] = 'charge';
                unset($data['selected_gateway']);
                $this->db->insert('payment', $data);            
                $query = $this->db->query('select LAST_INSERT_ID( ) AS last_id');
                $result = $query->result_array();
                if(isset($result[0]))
                {
                    $payment_id = $result[0]['last_id'];
                }

                $invoice_log_data = array();
                $invoice_log_data['date_time'] = gmdate('Y-m-d h:i:s');
                $invoice_log_data['booking_id'] = $this->input->post('booking_id');
                $invoice_log_data['user_id'] = $this->session->userdata('user_id');
                $invoice_log_data['action_id'] = CAPTURED_PAYMENT;
                $invoice_log_data['charge_or_payment_id'] = $payment_id;
                $invoice_log_data['new_amount'] = $this->input->post('payment_amount');
                if ($payment_id && $invoice_log_data['charge_or_payment_id']) {
                    $this->Payment_model->insert_payment_folio(array('payment_id' => $payment_id, 'folio_id' => $payment_folio_id));
                    $invoice_log_data['log'] = 'Payment Captured';
                    $this->Invoice_log_model->insert_log($invoice_log_data);
                }
                else {
                    $invoice_log_data['charge_or_payment_id'] = 0;
                    $invoice_log_data['log'] = isset($error) && $error ? $error : '';
                    $this->Invoice_log_model->insert_log($invoice_log_data);
                }

                $this->Bookings_model->update_booking_balance($data['booking_id']);
            }

            // show error
            if (!empty($error)) {
                $response = array("success" => false, "message" => $error);
            } else {
                $response = array("success" => true, "payment_id" => $payment_id);
            }

            echo json_encode($response);
        }
    }

}
