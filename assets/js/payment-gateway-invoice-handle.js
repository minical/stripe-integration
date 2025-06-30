
    stripe_token = "";
    is_stripe_token_available = false;

    let stripe = null;

    let card = null; // Make card accessible globally

    $(".use-payment-gateway-btn").css('display','block');

    // gateway button
    var $methods_list = $('select[name="payment_type_id"]');
    var gateway_button = $('input[name="stripe_use_gateway"]');
    var stripe_selected_gateway = $('input[name="stripe_use_gateway"]').data('gateway_name');
    
    var gatewayTypes = {
        'stripe': 'Stripe'
    };
    stripe_selected_gateway = gatewayTypes[stripe_selected_gateway];
    
    $methods_list.prop('disabled', false);
    gateway_button.prop('checked',0);

    gateway_button.on('click',function(){
        $that = $(this);
        
        var checked = $that.prop('checked');
        $methods_list.prop('disabled', checked);
        var manualPaymentCapture = $("#manual_payment_capture").val();
        if(checked)
        {
            
            $methods_list
                .append(
                $('<option></option>',{
                    id : 'gateway_option'
                })
                    .val('gateway')
                    .html(stripe_selected_gateway)
            );
            $methods_list.val('gateway');


            $.ajax({
                type: "POST",
                dataType: 'json',
                url: getBaseURL() + 'check_stripe_token_availability',
                data: {
                    customer_id : $("select[name='customer_id']").val()
                },
                success: function (response) {
                    console.log('response',response);

                    if(response.success){
                        is_stripe_token_available = true;
                    } else {
                        getCardToken();
                    }
                }
            });

            $("#add_payment_normal").hide();

            $(".add_payment_button").parent('.modal-footer').prepend(
                                                '<button type="button" class="btn btn-success add_stripe_payment" id="add_stripe_payment">'+
                                                    '<span alt="add" title="add">'+l('stripe-integration/Add Payment')+'</span>'+
                                                '</button>'
                                            );
            
            var available_gateway = $('.paid-by-customers').children('option:selected').data('available-gateway');
            
        }else{
            $('.cc_details').remove();
            $('#gateway_option').remove();
            $('#cvc-field').remove();

            $('#payment-form').parents('.form-group').remove();
        }
    });


    $("body").on("click", ".add_stripe_payment", async function () {

        $(this).html("Processing. . .");
        $(this).prop("disabled", true);

        if(is_stripe_token_available){
            proceedWithPayment();
            return true;
        }

        const statusContainer = document.getElementById('payment-status-container');
        const stripeToken = document.getElementById('stripe-token');

        // Tokenize card if card element is available
        if (card) {
            try {
                stripe.createToken(card).then(function(result) {
                    if (result.error) {
                        alert(result.error.message);

                        console.error('Token error:', result.error);
                        statusContainer.innerHTML = "Please enter valid card details.";
                        statusContainer.style.color = "red";
                        $(this).prop("disabled", false);
                        return;

                    } else {
                        console.log(`Result is`, result);

                        statusContainer.style.color = "black";

                        stripeToken.innerHTML = result.token.id;

                        document.getElementById('stripe-token').innerHTML = result.token.id;
                        document.getElementById('stripe-exp_month').innerHTML = result.token.card.exp_month;
                        document.getElementById('stripe-exp_year').innerHTML = result.token.card.exp_year;
                        document.getElementById('stripe-lastfour').innerHTML = result.token.card.last4;
                    
                        statusContainer.innerHTML = "Retrieve token successfully";
                        proceedWithPayment();

                    }
                });
            } catch (e) {
                console.error(e);
                statusContainer.innerHTML = "Payment Failed";
            }
        }
    });

    
    // show "Use Payment Gateway" option 
    $('.paid-by-customers').on('change', function(){
        var isGatewayAvailable = $(this).find('option:selected').attr('is-gateway-available');
        if(isGatewayAvailable == 'true'){
            $('.use-payment-gateway-btn').show();
            $('input[name="use_gateway"]').prop('checked', false);
            $('#cvc-field').remove();
            //$('select[name = "payment_type_id"]').attr('disabled');
            $checked = $('input[name="use_gateway"]').prop('checked');
            if($checked){
                $('select[name = "payment_type_id"]')
                        .append('<option id="gateway_option" value="gateway">'+stripe_selected_gateway+'</option>')
            }
        }
        else
        {
            $('.use-payment-gateway-btn').hide();
            $('select[name = "payment_type_id"]').removeAttr('disabled');
            $('#gateway_option').remove();
            $('input[name="use_gateway"]').prop('checked', 0);
        }
    });
    if( $('.paid-by-customers option:selected').attr('is-gateway-available') == 'true'){
        $('.use-payment-gateway-btn').show();
    }


async function getCardToken() {

    var postal_code = $('#postal_code').val();

    var html_content = '<div class="form-group">'+
                            '<label for="payment_amount" class="col-sm-4 control-label">'+
                                'Card Details'+
                            '</label>'+
                            '<div class="col-sm-8">'+
                                '<div id="payment-form">'+
                                    '<div id="payment-status-container" style="margin-bottom: 10px;"></div>'+
                                    '<div style="display:none;" id="stripe-token" style="margin-bottom: 10px;"></div>'+
                                    '<div style="display:none;" id="stripe-exp_month"></div>'+
                                    '<div style="display:none;" id="stripe-exp_year"></div>'+
                                    '<div style="display:none;" id="stripe-lastfour"></div>'+
                                    '<div id="card-element" style="padding: 12px; border: 1px solid #ccc; border-radius: 6px; margin-bottom: 12px;"></div>'+
                                    '<button style="display:none;" id="card-button" type="button" class="btn btn-success">Create Token</button>'+
                                '</div>'+
                            '</div>'+
                        '</div>';

    $(html_content).insertAfter('.use-payment-gateway-btn');

    console.log(innGrid.featureSettings.stripePublicKey);

    stripeGateway = true;

    stripe = Stripe(innGrid.featureSettings.stripePublicKey);
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

    card = elements.create('card', { style: style, hidePostalCode: true });
    card.mount('#card-element');
}

function proceedWithPayment() {
    
    // Introduce a delay of 2 seconds (2000 milliseconds) before proceeding
    setTimeout(function() {

        var ccData = { 
            "stripe_token":  $("#stripe-token").text() ,
            "stripe_exp_month":  $("#stripe-exp_month").text() ,
            "stripe_exp_year" : ($("#stripe-exp_year").text().substr(2, 4)) ,
            "stripe_lastfour": $("#stripe-lastfour").text() ,
            "customer_id": $("select[name='customer_id']").val(),
            "create_stripe_customer_from": "invoice_page"
        };

        $.ajax({
            type: "POST",
            dataType: 'json',
            url: getBaseURL() + 'add_stripe_token',
            data: {
                data : ccData
            },
            success: function (response) {
                console.log('response',response);

                // if(response.success){
                //     square_token = response.token;
                //     // square_customer_id = response.square_customer_id;
                // }
            }
        });
    }, 2000);

    setTimeout(function() {
        $.ajax({
            url    : getBaseURL() + 'add_stripe_payment',
            method : 'post',
            dataType: 'json',
            data   : {
                payment_amount: $("input[name='payment_amount']").val(),
                booking_id      : $("#booking_id").val(),
                payment_date    : innGrid._getBaseFormattedDate($("input[name='payment_date']").val()),
                payment_type_id : $("select[name='payment_type_id']").val(),
                customer_id     : $("select[name='customer_id']").val(),
                description     : $("textarea[name='description']").val(),
                folio_id        : $('#current_folio_id').val(),
                selected_gateway : $('input[name="'+innGrid.featureSettings.selectedPaymentGateway+'_use_gateway"]').data('gateway_name'),
                // cc_details : ccData
            },
            success: function (data) { 
                console.log('expire ',data);
                if (data == "You don't have permission to access this functionality."){
                    alert(data);
                    $(that).prop("disabled", false);
                    return;
                }
                
                if(data.success){
                   window.location.reload();
                }
                // else if(data.expire)
                // {
                //     window.location.href = getBaseURL() + 'settings/integrations/payment_gateways';
                // }
                else
                {
                    var error_html = "";
                    // console.log(jQuery.isArray( data.message ));
                    if(jQuery.isArray( data.message )){
                        $.each(data.message, function(i,v){
                            error_html += v.detail+'\n';
                        });
                        console.log(error_html);
                        $('#display-errors').find('.modal-body').html(error_html.replace(/\n/g,'<br/>'));
                        $('#display-errors').modal('show');
                        // alert(error_html);
                    } else {
                        alert(data.message ? data.message : data);
                    }
                    
                    
                    $(that).prop("disabled", false);
                }
            }
        });

    }, 5000); // 2000 milliseconds = 2 seconds
}