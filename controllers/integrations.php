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
        $this->load->model('../extensions/'.$this->module_name.'/models/Booking_model');
        $this->load->model('../extensions/'.$this->module_name.'/models/Card_model');
        $this->load->model('../extensions/'.$this->module_name.'/models/Customer_model');
        $this->load->model('../extensions/'.$this->module_name.'/models/Payment_model');

        $this->load->library('../extensions/'.$this->module_name.'/libraries/ProcessPayment');
        
        $this->load->library('paymentgateway');
		
        $view_data['menu_on'] = true;       
		$this->load->vars($view_data);
	}
    
    function asaas_payment_gateway()
    {       
        $data['selected_sidebar_link'] = 'Payment Gateways';
        $data['main_content'] = '../extensions/'.$this->module_name.'/views/payment_gateway_settings';
        
        $this->load->view('includes/bootstrapped_template', $data);
    }

    function update_asaas_payment_gateway_settings() {
        foreach($_POST as $key => $value)
        {
            if(
                $key != 'asaas_api_key' 
            )
            {
                $data[$key] = $this->input->post($key);
            }
        }

        if($data['selected_payment_gateway'] == 'asaas')
        {
            $meta = array(
                "asaas_api_key" => $_POST['asaas_api_key']
            );
            $data['gateway_meta_data'] = json_encode($meta);
        }
        
        $data['company_id'] = $this->company_id;
        $this->Payment_gateway_model->update_payment_gateway_settings($data);
        $this->_create_accounting_log("Update Payment Gateway Setting");
        
        echo json_encode($data);
    }

    function get_asaas_payment_gateway_settings() {
        $data = $this->processpayment->getGatewayCredentials();
        echo json_encode($data);
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
        $booking_data = $this->Booking_model->get_booking($booking_id);
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

    public function asaas_transaction_history() {

        $transactions = $this->processpayment->getCharges();
        $data['transactions'] = $transactions['data'];
        $data['main_content'] = '../extensions/'.$this->module_name.'/views/asaas_transaction_history';
        
        $this->load->view('includes/bootstrapped_template', $data);
    }

    public function verify_payment() {

        $payment_link_id = $this->input->post('payment_link_id');
        $payment_id = $this->input->post('payment_id');
        $customer_id = $this->input->post('customer_id');
        $charges = $this->processpayment->getCharges();

        $is_payment_varified = false;
        $payment_data = array();
        if(isset($charges['data']) && count($charges['data']) > 0){
            foreach($charges['data'] as $charge){
                if($charge['paymentLink'] == $payment_link_id){
                    $payment_data = $charge;
                    $is_payment_varified = true;
                    break;
                }
            }
        }

        if($is_payment_varified){

            $data['credit_card_id'] = null;
            $card_data = $this->Card_model->get_active_card($customer_id, $this->company_id);
            
            if (isset($card_data) && $card_data) {
                $data['credit_card_id'] = $card_data['id'];
            }

            $data = Array(
                "date_time" => gmdate("Y-m-d H:i:s"),
                "is_captured" => '1',
                "payment_status" => 'charge',
                "gateway_charge_id" => $payment_data['id'],
                "description" => ""
            );
            $this->Payment_model->update_payment($payment_id, $data);

            echo json_encode(array('success' => true ));
        } else {
            echo json_encode(array('success' => false, 'message' => 'Payment not done yet'));
        }
    }

    public function send_payment_link() {

        $due_date = $this->input->post('due_date');
        $payment_link_name = $this->input->post('payment_link_name');
        $payment_amount = $this->input->post('payment_amount');
        $payment_link = $this->processpayment->send_payment_link($payment_amount, $payment_link_name, $due_date);

        if(isset($payment_link['id']) && $payment_link['id'] && isset($payment_link['url']) && $payment_link['url']){

            $payment_gateway_used = $this->processpayment->getSelectedGateway();

            $payment_type    = $this->ci->processpayment->getPaymentGatewayPaymentType($this->selected_payment_gateway);
            $payment_type_id = $payment_type['payment_type_id'];

            $data = Array(
                "user_id" => $this->session->userdata('user_id'),
                "booking_id" => $this->input->post('booking_id'),
                "selling_date" => date('Y-m-d', strtotime($this->input->post('payment_date'))),
                "amount" => $payment_amount,
                "customer_id" => $this->input->post('customer_id'),
                "payment_type_id" => $payment_type_id,
                "payment_gateway_used" => $payment_gateway_used,
                "date_time" => gmdate("Y-m-d H:i:s"),
                "is_captured" => '0',
                "description" => $payment_link_name,
                "payment_status" => 'payment_link',
                "payment_link_id" => $payment_link['id']
            );
            $payment_id = $this->Payment_model->create_payment($data);

            echo json_encode(array('success' => true, 'payment_id' => $payment_id, 'payment_link_url' => $payment_link['url']));
        }
        
    }

}
