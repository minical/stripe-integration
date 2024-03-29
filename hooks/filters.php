<?php

add_filter('post.addnew.customer', 'stripe_tokenize', 10, 1);

function stripe_tokenize($data) {

    $filtered_data = $data;
    $customer_data = $filtered_data['customer_data'];
    unset($filtered_data['customer_data']);

    $CI = &get_instance();

    $CI->load->library('../extensions/stripe-integration/libraries/ProcessPayment');
    $token_response = $CI->processpayment->create_token($filtered_data['card']['service_code'],
     $filtered_data['card']['card_number'], $filtered_data['card']['expiration_month'], $filtered_data['card']['expiration_year']);

    if($token_response['success']){
        $response = $CI->processpayment->create_customer_id($token_response['token']);

        if($response['success']){
            $card_details = array(
                'is_primary' => 1,
                'customer_id' => $customer_data['customer_id'],
                'customer_name' => $customer_data['customer_name'],
                'card_name' => '',
                'company_id' => $customer_data['company_id'],
                'cc_expiry_month' => (isset($customer_data['cc_expiry_month']) ? $customer_data['cc_expiry_month'] : null),
                'cc_expiry_year' => (isset($customer_data['cc_expiry_year']) ? $customer_data['cc_expiry_year'] : null),
            );
    
            $card_details['cc_cvc_encrypted'] = null;
            $card_details['cc_number'] = 'XXXX XXXX XXXX ' . substr($filtered_data['card']['card_number'], -4);
            $meta['customer_id'] = $response['customer_id'];
            if(
                isset($filtered_data['tokenization_response']) &&
                isset($filtered_data['tokenization_response']['data']) &&
                isset($filtered_data['tokenization_response']['data']['attributes']) &&
                isset($filtered_data['tokenization_response']['data']['attributes']['card_token']) &&
                $filtered_data['tokenization_response']['data']['attributes']['card_token']
            ){
                $meta['token'] = $filtered_data['tokenization_response']['data']['attributes']['card_token'];
            } else {
                $meta['token'] = $token_response['token'];
            }
            $card_details['customer_meta_data'] = json_encode($meta);
            $cust_data = $CI->Card_model->get_card_details($customer_data['customer_id'], $customer_data['company_id']);
            
            if($cust_data){
                $CI->Card_model->update_customer_card($cust_data['id'], $cust_data['customer_id'], $card_details);
            } else {
                $CI->Card_model->create_customer_card_info($card_details);
            }
        }
    }

    $data['tokenization_response'] = $response;

    return $data;
}

