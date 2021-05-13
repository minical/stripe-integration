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
        
        $this->load->library('paymentgateway');
		
        $view_data['menu_on'] = true;       
		$this->load->vars($view_data);
	}
    
    function stripe_payment_gateway()
    {       
        $data['selected_sidebar_link'] = 'Payment Gateways';
        $data['main_content'] = '../extensions/'.$this->module_name.'/views/payment_gateway_settings';
        
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

}
