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
}