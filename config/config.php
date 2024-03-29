<?php 

$config = array(
    "name" => "Stripe Payment Gateway",
    "description" => "This extension is devised as the channel to make payments. The procedure to make payments includes the customer requiring to fill in some details, like credit/debit card number, expiry date, and CVV.",
    "is_default_active" => 1,
    "version" => "1.0.0",
    "logo" => "image/logo.png",
    "setting_link" => "stripe_payment_gateway",
    "gateway_key" => "stripe",
    "categories" => array("payment_process"),
    "supported_in_minimal" => true,
    "marketplace_product_link" => "https://marketplace.minical.io/product/stripe-payment-gateway"
);