<?php

class Customer_model extends CI_Model {

	function __construct()
    {
        parent::__construct();
    }

    function get_customer($customer_id)
	{
		$this->db->where('customer_id', $customer_id);
    	$this->db->from('customer');
        $query = $this->db->get();
        $result = $query->result_array();
		
		$customer = null;
		if ($this->db->_error_message())
		{
			show_error($this->db->_error_message());
		}
		
		if ($query->num_rows >= 1)
		{
			$customer = $result[0];

			$customer['customer_fields'] = $this->get_customer_fields($customer_id);
		}

        return $customer;
	}

    function get_staying_customers($booking_id)
    {
    	if(is_array($booking_id))
    	{
    		$select = " DISTINCT c.customer_id, ";
    		$booking_ids_str = implode(",", $booking_id);
    		$where = " b.booking_id IN ($booking_ids_str) AND ";
    	}
    	else
    	{
    		$select = "";
    		$where = " b.booking_id = '$booking_id' AND ";
    	}
        $sql = "SELECT $select c.*
				FROM customer as c, booking_staying_customer_list as bscl, booking as b
				WHERE
					$where
					b.booking_id = bscl.booking_id AND
					bscl.customer_id = c.customer_id
			";

        $q = $this->db->query($sql);

        // return result set as an associative array
        return $q->result_array();
    }

    function get_customer_fields($customer_id)
    {
		$this->db->where('company_id', $this->company_id);
		$this->db->where('show_on_customer_form', 1);
		$this->db->where('is_deleted', 0);
    	$this->db->from('customer_field as cf');
    	$this->db->join('customer_x_customer_field as cxcf', "cxcf.customer_field_id = cf.id and cxcf.customer_id = '$customer_id'", 'left');
        $query = $this->db->get();
        $customer_fields_result = $query->result_array();

        $customer_fields = array();
        foreach($customer_fields_result as $field)
        {
        	$customer_fields[$field['id']] = $field['value'];
        }

        return $customer_fields;
	}
}