<?php

class Employee_log_model extends CI_Model {

    function __construct()
    {        
        parent::__construct();
    }		
	
	function insert_log($data)
    {
	   // $data = (object) $data;        
	    $this->db->insert("employee_log", $data);
		
	/*	if ($this->db->_error_message()) 
		{
			show_error($this->db->_error_message());
		}		
    
        $query = $this->db->query('select LAST_INSERT_ID( ) AS last_id');
		$result = $query->result_array();
        if(isset($result[0]))
        {  
            return $result[0]['last_id'];
        }
		else
        {  
            return null;
        }	*/
    }	
    
  
}