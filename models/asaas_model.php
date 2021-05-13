<?php

class Asaas_model extends CI_Model {

    function __construct()
    {
        parent::__construct();
    }
    
    function get_booking_details($asaas_payment_id)
    {
        $this->db->select('b.*');
        $this->db->where('p.gateway_charge_id', $asaas_payment_id);
        $this->db->from('payment as p');
        $this->db->join('booking as b', "b.booking_id = p.booking_id", 'right');
        $query = $this->db->get();
        if($query->num_rows() >= 1){
            return $query->row_array();
        }
        return NULL;
    }
}
