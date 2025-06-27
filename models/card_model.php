<?php
class Card_model extends CI_Model {

	function __construct()
    {
        parent::__construct();
    }
    
    function get_customer_cards($customer_id, $is_primary = false)
	{
		$this->db->select('cc.*, c.stripe_customer_id');
		$this->db->where('cc.customer_id', $customer_id);
		$this->db->where('cc.is_card_deleted', 0);

		if($is_primary)
			$this->db->where('cc.is_primary', 1);

    	$this->db->from('customer_card_detail as cc');
		$this->db->join('customer as c', 'cc.customer_id = c.customer_id', 'left');
        $query = $this->db->get();		
		
        if ($this->db->_error_message())
		{
			show_error($this->db->_error_message());
		}
		
		if ($query->num_rows >= 1)
		{
			return $query->result_array();
		}
        return null;
	}

    function get_active_card($customer_id, $company_id)
	{
		$this->db->where('customer_id', $customer_id);
        $this->db->where('company_id', $company_id);
        $this->db->where('is_primary', 1);
        $this->db->where('is_card_deleted', 0);
    	$this->db->from('customer_card_detail');
        $query = $this->db->get();
        $result = $query->result_array();
		
		if ($this->db->_error_message())
		{
			show_error($this->db->_error_message());
		}
		$customer = "";
		if ($query->num_rows >= 1)
		{
			$customer = $result[0];
		}

        return $customer;
	} 
    
    function get_customer_card_detail($customer_id)
	{
        $sql = "
			SELECT 
				customer.customer_id, customer.customer_name, customer.company_id, 
				customer_card_detail.id, customer_card_detail.is_primary, customer_card_detail.evc_card_status, 
				customer_card_detail.card_name, customer_card_detail.cc_number, customer_card_detail.cc_expiry_month,
				customer_card_detail.cc_expiry_year, customer_card_detail.cc_tokenex_token, customer_card_detail.cc_cvc_encrypted, customer_card_detail.is_card_deleted
			FROM customer 
			LEFT JOIN customer_card_detail ON customer.customer_id = customer_card_detail.customer_id
			WHERE 
				customer.customer_id = '$customer_id' ";
		
        $query = $this->db->query($sql);
        $result = $query->result_array();
		$customer = null;
		if ($this->db->_error_message())
		{
			show_error($this->db->_error_message());
		}
		
		if ($query->num_rows >= 1)
		{
			$customer = $result;
		}

        return $customer;
	}
    
    function update_customer_card_is_primary_card_table($customer_id, $card_id, $active, $company_id = null)
    {
        if($active == "active"){
            $data['is_primary'] = 0;
            $this->db->where('customer_id', $customer_id);
            $this->db->where('company_id', $company_id);
            $this->db->update("customer_card_detail", $data);
            
            $data['is_primary'] = 1;
            $this->db->where('id', $card_id);
            $this->db->where('customer_id', $customer_id);
            $this->db->where('company_id', $company_id);
            $this->db->update("customer_card_detail", $data);
        }else{
            $data['is_primary'] = 0;
            $this->db->where('id', $card_id);
            $this->db->where('customer_id', $customer_id);
            $this->db->where('company_id', $company_id);
            $this->db->update("customer_card_detail", $data);
            //echo $this->db->last_query();
            if($this->db->affected_rows() > 0){
                return true;
            }else{
                return false;
            }
        }

    }

    function create_customer_card_info($data)
    {
        $data = (object)$data;
        $this->db->insert("customer_card_detail", $data);
        if ($this->db->_error_message())
		{
            show_error($this->db->_error_message());
		}
    }

    function update_customer_primary_card($customer_id, $data)
    {   
		$data = (object) $data;
        $this->db->where('is_primary', 1);
		$this->db->where('customer_id', $customer_id);
        $this->db->update("customer_card_detail", $data);
        if($this->db->affected_rows() > 0){
            return true;
        }else{
            return false;
        }
    }

    function update_customer_card_info($customer_id, $data, $card_id = null)
    {
        $data = (object) $data;
		$this->db->where('customer_id', $customer_id);

		if($card_id)
			$this->db->where('id', $card_id);

        $this->db->update("customer_card_detail", $data);
        if($this->db->affected_rows() > 0){
            return true;
        }else{
            return false;
        }
    }
}