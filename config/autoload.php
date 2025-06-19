<?php 
$config['js-files'] = array(
    array(
        "file" => 'assets/js/payment-gateway-settings-handle.js',
         "location" => array(
          "integrations/stripe_payment_gateway",
        ),
    ),
    array(
        "file" => 'assets/js/payment-gateway-invoice-handle.js',
         "location" => array(
          "invoice/show_invoice",
          "invoice/show_master_invoice",
        ),
    ),
    array(
        "file" => 'https://js.stripe.com/v3/',
        "location" => array(
            "invoice/show_invoice",
            "booking/index",
            "online_reservation/book_reservation",
            "online_group_reservation/book_reservation",
        ),
    ),
    array(
        "file" => 'assets/js/stripe-card-setting.js',
        "location" => array(
            "booking/index",
        ),
    ),
    array(
        "file" => 'assets/js/stripe-multiple-card-setting.js',
        "location" => array(
            "booking/index",
        ),
    ),
    array(
        "file" => 'assets/js/bookingengine-setting.js',
        "location" => array(
            "online_reservation/book_reservation",
            "online_group_reservation/book_reservation",
        )
    )
);


$config['css-files'] = array();





