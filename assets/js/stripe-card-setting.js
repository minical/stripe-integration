var stripeGateway = false;

document.addEventListener("post.open_customer_model", function (e) {
    console.log('e',e);
    console.log('e.detail',e.detail);
    console.log('e.detail.customer_id',e.detail.customer_id);
    if (
            (
                e && 
                e.detail && 
                e.detail.customer_id == ''
            ) || 
            (
                $('#cc_number').val() == '' && 
                $('input[name="cc_expiry"]').val() == '' &&
                $('input[name="cvc"]').val() == ''
            ) 
        ){

        $('.cc_field').css('display', 'none');
         
         
        var stripe_card_button = '<div class="form-group form-group-sm stripe_card_button">'+
                                    '<label for="stripe_card_data" class="col-sm-3 control-label">Stripe Card Details</label>'+
                                    '<div class="col-sm-9">'+
                                        '<button type="button" class="btn btn-info stripe_card_btn" onclick="show_iframe()">Add Card Details</button>'+
                                    '</div>'+
                                '</div>';

        // var stripe_card_button = '<div id="card-element"><!-- Stripe will inject an iframe here --></div>'+
        //                             '<button id="submit">Pay Now</button>';
         $('.form-group.form-group-sm.customer_field_12').after(stripe_card_button);
    } else {
        $('.cc_field').css('display', 'block'); 
    }

}); 

async function show_iframe(){

    var stripe_iframe = '<div class="col-sm-9">'+
                            '<div id="payment-form">'+
                                '<div id="payment-status-container" style="margin-bottom: 10px;"></div>'+
                                '<div style="display:none;" id="stripe-token" style="margin-bottom: 10px;"></div>'+
                                '<div style="display:none;" id="stripe-exp_month"></div>'+
                                '<div style="display:none;" id="stripe-exp_year"></div>'+
                                '<div style="display:none;" id="stripe-lastfour"></div>'+
                                '<div id="card-element" style="padding: 12px; border: 1px solid #ccc; border-radius: 6px; margin-bottom: 12px;"></div>'+
                                '<button style="display:none;" id="card-button" type="button" class="btn btn-success">Create Token</button>'+
                            '</div>'+
                        '</div>';


    $('.stripe_card_button').append(stripe_iframe);
    $('.stripe_card_btn').parent('div').remove();

    console.log(innGrid.featureSettings.stripePublicKey);

    stripeGateway = true;

    const stripe = Stripe(innGrid.featureSettings.stripePublicKey);
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

    const card = elements.create('card', { style: style });
    card.mount('#card-element');

    const cardButton = document.getElementById('card-button');
    cardButton.addEventListener('click', async () => {
        const statusContainer = document.getElementById('payment-status-container');
        const stripeToken = document.getElementById('stripe-token');

        try {
            stripe.createToken(card).then(function(result) {
                if (result.error) {
                    alert(result.error.message);
                } else {
                    console.log(`Result is`, result);

                    statusContainer.innerHTML = "Retrieve token successfully";
                    stripeToken.innerHTML = result.token.id;

                    document.getElementById('stripe-token').innerHTML = result.token.id;
                    document.getElementById('stripe-exp_month').innerHTML = result.token.card.exp_month;
                    document.getElementById('stripe-exp_year').innerHTML = result.token.card.exp_year;
                    document.getElementById('stripe-lastfour').innerHTML = result.token.card.last4;
                }
            });
        } catch (e) {
            console.error(e);
            statusContainer.innerHTML = "Payment Failed";
        }
    });
}