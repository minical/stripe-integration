
<?php

class Booking_model extends CI_Model {

    function __construct()
    {
        parent::__construct();
    }
    
function get_booking($booking_id, $is_company = true)
    {   
        $this->db->where('b.booking_id', $booking_id);
        $this->db->from('booking as b');
        if($is_company)
            $this->db->join('company as c', 'c.company_id = b.company_id');
        $query = $this->db->get();
        $result = $query->result_array();
        
        if ($this->db->_error_message())
        {
            show_error($this->db->_error_message());
        }
        
        if ($query->num_rows >= 1)
        {
            return $result[0];
        }
        return null;
    }
    
}