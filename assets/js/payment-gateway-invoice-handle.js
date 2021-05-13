
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
            if(manualPaymentCapture == 1)
            {
                $('#auth_and_capture').removeClass('hidden');
                $('#authorize_only').removeClass('hidden');
                $('#add_payment_normal').addClass('hidden');
            }
            else{
                $('#add_payment_button').removeClass('hidden');
                $('#add_payment_normal').addClass('hidden');
                $('#auth_and_capture').addClass('hidden');
                $('#authorize_only').addClass('hidden');
            }
            $methods_list
                .append(
                $('<option></option>',{
                    id : 'gateway_option'
                })
                    .val('gateway')
                    .html(stripe_selected_gateway)
            );
            $methods_list.val('gateway');
            
            var available_gateway = $('.paid-by-customers').children('option:selected').data('available-gateway');
            
            // false by default. until we find proper solution to store cvc
            if(false && available_gateway == 'tokenex')
            {
                $that.parents('#use-gateway-div').append(
                    $('<div/>',{
                        class: 'col-sm-10',
                        id: 'cvc-field'
                    }).append(
                         $("<label/>", {
                            for : "cvc",
                            class: "col-sm-3 control-label",
                            text: l("CVC", true)
                        })
                    ).append(
                        $("<div/>", {
                            class: "col-sm-9"
                        }).append(
                        $("<input/>", {
                            class: "form-control",
                            name: "cvc",
                            placeholder: '***',
                            type: 'password',
                            maxlength: 4,
                            autocomplete: false,
                            required: "required"
                        })
                        )
                    )
                );
            }
        }else{
            if(manualPaymentCapture == 1)
            {
                $('#auth_and_capture').addClass('hidden');
                $('#authorize_only').addClass('hidden');
                $('#add_payment_normal').removeClass('hidden');
            }
            else{
                $('#add_payment_button').addClass('hidden');
                $('#add_payment_normal').removeClass('hidden');
                $('#auth_and_capture').removeClass('hidden');
                $('#authorize_only').removeClass('hidden');
            }
            $('#gateway_option').remove();
            $('#cvc-field').remove();
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
