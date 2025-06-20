// $('.cc_details').css('display', 'none');
var customerID;
var gatewayName = 'stripe';
var sandBoxMode = false;

let card = null; // Make card accessible globally
let stripe = null;

var stripe_card_button = '<div class="form-group form-group-sm stripe_card_button">'+
                            '<label for="stripe_card_data" class="col-sm-3 control-label">Stripe Card Details</label>'+
                            '<div class="col-sm-9">'+
                                '<button type="button" class="btn btn-info stripe_card_btn" onclick="show_iframe()">Add Card Details</button>'+
                            '</div>'+
                        '</div>';


$('.add_stripe_details').hide();
$('.add_stripe_details').after(stripe_card_button);

setTimeout(function(){
	$('.stripe_card_btn').trigger('click');
}, 500);

async function show_iframe() {
    const stripe_iframe = 
        '<div id="payment-form">'+
            '<div id="payment-status-container" style="margin-bottom: 10px;"></div>'+
            '<div style="display:none;" id="stripe-token" style="margin-bottom: 10px;"></div>'+
            '<div style="display:none;" id="stripe-exp_month"></div>'+
            '<div style="display:none;" id="stripe-exp_year"></div>'+
            '<div style="display:none;" id="stripe-lastfour"></div>'+
            '<div id="card-element" style="padding: 12px; border: 1px solid #ccc; border-radius: 6px; margin-bottom: 12px;"></div>'+
            '<button style="display:none;" id="card-button" type="button" class="btn btn-success">Create Token</button>'+
        '</div>';

    $('.stripe_card_btn').parent('div').append(stripe_iframe);
    $('.stripe_card_btn').remove();

    if (!stripePublicKey) {
        stripePublicKey = $('#stripe_public_key').val();
    }

    stripe = Stripe(stripePublicKey);
    const elements = stripe.elements();
    
    const style = {
        base: {
            fontSize: '16px',
            color: '#32325d',
            fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
            '::placeholder': {
                color: '#aab7c4'
            }
        },
        invalid: {
            color: '#fa755a',
            iconColor: '#fa755a'
        }
    };

    // card = elements.create('card', { style: style });
    card = elements.create('card', { style: style, hidePostalCode: true });
    card.mount('#card-element');
}

$(".book_now").on('click', async function () {

	$('.book_now').val('Processing...');
	$('.book_now').prop('disabled', true);

    const statusContainer = document.getElementById('payment-status-container');
    const stripeToken = document.getElementById('stripe-token');

    // Tokenize card if card element is available
    if (card) {
        try {
            stripe.createToken(card).then(function(result) {
                if (result.error) {
                    alert(result.error.message);
                } else {
                    console.log(`Result is`, result);

                    stripeToken.innerHTML = result.token.id;

                    document.getElementById('stripe-token').innerHTML = result.token.id;
                    document.getElementById('stripe-exp_month').innerHTML = result.token.card.exp_month;
                    document.getElementById('stripe-exp_year').innerHTML = result.token.card.exp_year;
                    document.getElementById('stripe-lastfour').innerHTML = result.token.card.last4;
                
                    statusContainer.innerHTML = "Retrieve token successfully";
                    proceedWithBooking();

                }
            });
        } catch (e) {
            console.error(e);
            statusContainer.innerHTML = "Payment Failed";
        }
    } else {
        alert("Please add card details before booking.");
    }
});

function proceedWithBooking() {
    var current_url = $(location).attr('href');
    var parts = current_url.split("/");
    var company_id = parts[parts.length - 1];

    var cont_name = parts[parts.length - 3];

    var controller = 'online_reservation';

    if(cont_name == 'online_group_reservation'){
    	controller = 'online_group_reservation';
    }

    var customer_email = $("input[name='customer_email']").val();
    var customer_name = $("input[name='customer_name']").val();
    var phone = $("input[name='phone']").val();
    var address = $("input[name='address']").val();
    var city = $("input[name='city']").val();
    var region = $("input[name='region']").val();
    var country = $("input[name='country']").val();
    var postal_code = $("input[name='postal_code']").val();
    var special_requests = $("textarea[name='special_requests']").val();

    var stripe_token = $('#stripe-token').text();
    var stripe_exp_month = $('#stripe-exp_month').text();
    var stripe_exp_year = $('#stripe-exp_year').text();
    var stripe_lastfour = $('#stripe-lastfour').text();

    stripe_exp_year = stripe_exp_year.substr(2, 4);

    var customerCardData = {
        stripe_token: stripe_token,
        cc_number: "XXXX XXXX XXXX " + stripe_lastfour,
        cc_expiry_month: stripe_exp_month,
        cc_expiry_year: stripe_exp_year,
        customer_name: customer_name,
        email: customer_email,
        address: address,
        country: country,
        postal_code: postal_code
    };

    $.ajax({
        type: "POST",
        url: getBaseURL() + controller + "/book_reservation/" + company_id,
        data: {
            customer_data: customerCardData,
            customer_name: customer_name,
            customer_email: customer_email,
            phone: phone,
            address: address,
            city: city,
            region: region,
            country: country,
            postal_code: postal_code,
            special_requests: special_requests
        },
        dataType: "json",
        success: function (res) {
            console.log(res);
            if (res.error && res.error_msg) {
                console.log(res.error_msg);
            } else {
                // Redirect to success page
                $(location).attr('href', getBaseURL() + controller + "/reservation_success/" + company_id);
            }
        }
    });
}
