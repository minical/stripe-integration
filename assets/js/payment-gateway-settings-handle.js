var settings;
innGrid.ajaxCache = innGrid.ajaxCache || {};

innGrid._getStripeForm = function() {
    return $("<div/>", {})
        .append(innGrid._getHorizontalInput("Publishable Key", "stripe_publishable_key", settings.stripe.stripe_publishable_key))
        .append(innGrid._getHorizontalInput("Secret Key", "stripe_secret_key", settings.stripe.stripe_secret_key));
};

innGrid._getHorizontalInput = function (label, name, value)
{
    if( 
            name == 'stripe_publishable_key' || 
            name == 'stripe_secret_key'
        )
    {
        var sensitiveData = '<a title = "Show '+label+'" class="show_password" href="javascript:"><i class="fa fa-eye" ></i></a>';
    }
        
    return $("<div/>", {
        class: "form-group form-group-sm show_field"
    }).append(
        $("<label/>", {
            for: name,
            class: "col-sm-3 control-label",
            text: label
        })
    ).append(
        sensitiveData
    ).append(
        $("<div/>", {
            class: "col-sm-8"
        }).append(
            $("<input/>", {
                class: "form-control sensitive_field",
                name: name,
                value: value,
                type: (name == 'gateway_login' || name == 'gateway_app_id' || name == 'gateway_user') ? "text" : "password"
            })
        )
    )
};

$('body').on('click', '.show_password', function(){
    $(this).parents('.show_field').find('.sensitive_field').attr('type','text');
    var thats = $(this);
    setTimeout( function(){
        thats.parents('.show_field').find('.sensitive_field').attr('type','password'); 
    }, 3000);
});

innGrid._updatePaymentGatewayForm = function(selected_payment_gateway) {
    if (selected_payment_gateway === '') {
        $("#form-div").html('');
        $("#update-button").text(l("Update", true));
    }
    else if (selected_payment_gateway === 'stripe') {
        $("#form-div").html(innGrid._getStripeForm());
        $("#update-button").text(l("Update", true));
    }
};

$(function (){

    var gatewayTypes = {
        'Stripe': 'stripe',
    };
    
    // load saved payment gateway settings data
    if(!innGrid.ajaxCache.paymentGatewaySettings)
    {
        $.ajax({
            type: "POST",
            dataType: 'json',
            url: getBaseURL() + 'settings/integrations/get_stripe_payment_gateway_settings',
            success: function( data ) {
                settings = data;
                innGrid.ajaxCache.paymentGatewaySettings = data;
                for (var key in gatewayTypes) {
                    var option = $("<option/>", {
                                    value: gatewayTypes[key],
                                    text: key
                                });

                    $("[name='payment_gateway']").append(option);

                    if (data.selected_payment_gateway == gatewayTypes[key]) {
                        option.prop('selected', true);
                    }
                }

                $gateway = $("select[name='payment_gateway']");
                $gateway.change(function () {
                    var selected_payment_gateway = $gateway.val();
                    innGrid._updatePaymentGatewayForm(selected_payment_gateway);
                });

                $gateway.trigger("change");
            }

        });
    }
    else
    {
        settings = data = innGrid.ajaxCache.paymentGatewaySettings;
        for (var key in gatewayTypes) {
            var option = $("<option/>", {
                            value: gatewayTypes[key],
                            text: key
                        });

            $("[name='payment_gateway']").append(option);

            if (data.selected_payment_gateway == gatewayTypes[key]) {
                option.prop('selected', true);
            }
        }

        $gateway = $("select[name='payment_gateway']");
        $gateway.change(function () {
            var selected_payment_gateway = $gateway.val();
            innGrid._updatePaymentGatewayForm(selected_payment_gateway);
        });

        $gateway.trigger("change");
    }
    
    $("#update-button").on("click", function () {
        var valid = false;
        var selected_payment_gateway = $("select[name='payment_gateway']").val();
        var fields = {};
        fields['selected_payment_gateway'] = selected_payment_gateway;
        //for each vars, push
        $("#form-div input").each(function() {
            fields[$(this).attr("name")] = $(this).val();
        });

        switch (selected_payment_gateway) {
            default:
                valid = true;
                break;
            case 'stripe':
                if (fields['stripe_publishable_key'] != '' && fields['stripe_secret_key'] != '') {
                    valid = true;
                } else {
                    alert(l('Please fill all fields'));
                }
                break;
        }

        if (valid) {
            $.ajax({
                type    : "POST",
                dataType: 'json',
                url     : getBaseURL() + 'settings/integrations/update_stripe_payment_gateway_settings/',
                data: fields,
                success: function( data ) {
                    if(data.authorizationCodeUrl)
                    {
                        var url = data.authorizationCodeUrl.replace(/\"/g, "");
                        url = url.replace(/\/+/g, '');
                        window.location.href = url;
                    }
                    else
                        alert(l("Settings updated."));
                }
            });
        }


    }); //--update on click


});
    