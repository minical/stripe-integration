
<?php

class Bookings_model extends CI_Model {

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

    function update_booking_balance($booking_id, $return_type = 'balance', $booking_details = null, $booking_extras = null) 
    {       
        if (!$booking_id) {
            return null;
        }

        $sql = "SELECT *,
                    IFNULL(
                    (
                        SELECT 
                            SUM(charge_amount) as charge_total
                        FROM (
                            SELECT
                               (
                                   ch.amount +
                                   SUM(
                                        IF(tt.is_tax_inclusive = 1,
                                            0,
                                            (ch.amount * IF(tt.is_percentage = 1, IF(tt.is_brackets_active, tpb.tax_rate, tt.tax_rate), 0) * 0.01) +
                                            IF(tt.is_percentage = 0, IF(tt.is_brackets_active, tpb.tax_rate, tt.tax_rate), 0)
                                        )
                                    )
                               ) as charge_amount                     
                            FROM charge as ch
                            LEFT JOIN charge_type as ct ON ch.charge_type_id = ct.id AND ct.is_deleted = '0'
                            LEFT JOIN charge_type_tax_list AS cttl ON ct.id = cttl.charge_type_id 
                            LEFT JOIN tax_type AS tt ON tt.tax_type_id = cttl.tax_type_id AND tt.is_deleted = '0'
                            LEFT JOIN tax_price_bracket as tpb 
                                ON tpb.tax_type_id = tt.tax_type_id AND ch.amount BETWEEN tpb.start_range AND tpb.end_range
                            WHERE
                                ch.is_deleted = '0' AND
                                ch.booking_id = '$booking_id'  
                            GROUP BY ch.charge_id
                        ) as total
                    ), 0    
                ) as charge_total,
                IFNULL(
                    (
                        SELECT SUM(p.amount) as payment_total
                        FROM payment as p, payment_type as pt
                        WHERE
                            p.is_deleted = '0' AND
                            #pt.is_deleted = '0' AND
                            p.payment_type_id = pt.payment_type_id AND
                            p.booking_id = b.booking_id

                        GROUP BY p.booking_id
                    ), 0
                ) as payment_total
            FROM booking as b
            LEFT JOIN booking_block as brh ON b.booking_id = brh.booking_id
            WHERE b.booking_id = '$booking_id'
        ";
        
        $query = $this->db->query($sql);
        $result = $query->result_array();
        $booking = null;
        if ($query->num_rows >= 1 && isset($result[0]))
        {
            $booking =  $result[0];
        }
        
        if($booking)
        {
            $forecast = $this->forecast_charges->_get_forecast_charges($booking_id, true, $booking_details);
            $forecast_extra = $this->forecast_charges->_get_forecast_extra_charges($booking_id, true, $booking_details, $booking_extras);
            $booking_charge_total_with_forecast = (floatval($booking['charge_total']) + floatval($forecast['total_charges']) + floatval($forecast_extra));
            $data = array(
                'booking_id' => $booking_id,
                'balance' => $this->jsround(floatval($booking_charge_total_with_forecast) - floatval($booking['payment_total']), 2),
                'balance_without_forecast' => $this->jsround(floatval($booking['charge_total']) - floatval($booking['payment_total']), 2)
            );
            $this->update_booking($booking_id, $data);
            return $data[$return_type];
        }
        return null;
    }
    
     function get_bookings_by_group_id($group_id, $is_select_balacne = false)
    {
        $select_balacne = '';
        if($is_select_balacne)
        {
            $select_balacne = ", booking.balance";
        }
        $sql = "
                SELECT
                    booking_x_booking_linked_group.booking_id,
                    booking_x_booking_linked_group.booking_group_id,
                    booking_block.room_id,
                    room.room_name $select_balacne
                FROM
                    booking_x_booking_linked_group
                LEFT JOIN
                    booking_block ON booking_x_booking_linked_group.booking_id = booking_block.booking_id
                LEFT JOIN
                    booking ON booking.booking_id = booking_block.booking_id
                LEFT JOIN
                    room ON booking_block.room_id = room.room_id
                WHERE booking_group_id = $group_id AND booking.is_deleted = 0 AND booking.state < 3 ";
        $query = $this->db->query($sql);
        if($query->num_rows() >= 1)
        {
            return $query->result_array();
        }
        return NULL; 
    }

    function jsround($float, $precision = 0){
        $float = floatval(number_format($float, 12, '.', ''));
        if($float < 0){
           return round($float, $precision, PHP_ROUND_HALF_DOWN);
        }
        return round($float, $precision);
    }

    function update_booking($booking_id, $data) 
    {       
        $data = (object) $data;
        $this->db->where('booking_id', $booking_id);
        $this->db->update("booking", $data);        
    }
}