
    // gateway button
    var $methods_list = $('select[name="payment_type_id"]');
    var asaas_gateway_button = $('input[name="asaas_use_gateway"]');
    var asaas_selected_gateway = $('input[name="asaas_use_gateway"]').data('gateway_name');
    
    var gatewayTypes = {
        'asaas': 'Asaas'
    };
    asaas_selected_gateway = gatewayTypes[asaas_selected_gateway];
    
    $methods_list.prop('disabled', false);
    asaas_gateway_button.prop('checked',0);

    asaas_gateway_button.on('click',function(){
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
                    .html(asaas_selected_gateway)
            );
            $methods_list.val('gateway');
            
            var available_gateway = $('.paid-by-customers').children('option:selected').data('available-gateway');
            
            
        }else{
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
                        .append('<option id="gateway_option" value="gateway">'+asaas_selected_gateway+'</option>')
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

var paymentLinkHtml =   '<div class="col-sm-4"></div>'+
                        '<div class="col-sm-2">'+
                            '<input type="checkbox" class="form-control use-gateway" data-gateway_name="'+asaas_selected_gateway.toLowerCase()+'" name="'+asaas_selected_gateway.toLowerCase()+'_payment_link" type="payment_link">'+
                            '<p style="margin: -37px 41px 0px;"><b>Payment Link</b></p>'+
                        '</div>';

$('#use-gateway-div').append(paymentLinkHtml);

asaas_gateway_button.parent('div').append('<p style="margin: -37px 41px; width:90px;"><b>Credit / Debit Charge</b></p>');



// var $methods_list = $('select[name="payment_type_id"]');
    var asaas_payment_button = $('input[name="asaas_payment_link"]');
    var asaas_payment = $('input[name="asaas_payment_link"]').data('gateway_name');
    var asaas_payment_type = $('input[name="asaas_payment_link"]').data('type');
    
    asaas_payment = gatewayTypes[asaas_payment];
    
    $methods_list.prop('disabled', false);
    asaas_payment_button.prop('checked',0);

    asaas_payment_button.on('click',function(){
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
                    .html(asaas_payment)
            );
            $methods_list.val('gateway');

            $(".add_payment_button").parent('.modal-footer').prepend(
                                                    '<button type="button" class="btn btn-success send_payment_link" id="send_payment_link">'+
                                                        '<span alt="add" title="add">Send Payment Link</span>'+
                                                    '</button>'
                                                );
            $payment_link_div = '<div class="form-group payment_link_div">'+
                                    '<label for="payment_link" class="col-sm-4 control-label">'+
                                        '<span alt="payment_link" title="amount">Payment link name</span></label>'+
                                    '<div class="col-sm-8">'+
                                        '<input type="text" class="form-control" name="payment_link_name" placeholder="Enter name of payment link">'+
                                    '</div>'+
                                '</div>'+
                                '<div class="form-group payment_link_div">'+
                                    '<label for="due_date" class="col-sm-4 control-label">'+
                                        '<span alt="due_date" title="amount">Due date limit days</span></label>'+
                                    '<div class="col-sm-8">'+
                                        '<input type="text" class="form-control" name="due_date" placeholder="Enter days of due date">'+
                                    '</div>'+
                                '</div>';

            $($payment_link_div).insertAfter('.use-payment-gateway-btn');
            $("#add_payment_normal").addClass('hidden');
            
            
            var available_gateway = $('.paid-by-customers').children('option:selected').data('available-gateway');
            
        }else{

            $("#add_payment_normal").removeClass('hidden');
            $(".send_payment_link").remove();
            $(".payment_link_div").remove();
            
            $('#gateway_option').remove();
            $('#cvc-field').remove();
        }
    });

    $('.use-gateway').on('change', function() {
        $('.use-gateway').not(this).prop('checked', false);  
    });


    $(".send_payment_link").prop("disabled", false);
    
    $("body").on("click", ".send_payment_link", function () {

        $(this).html("Processing. . .");
        $(this).prop("disabled", true);
        
        $.ajax({
            url    : getBaseURL() + 'send_payment_link',
            method : 'post',
            dataType: 'json',
            data   : {
                payment_link_name: $("input[name='payment_link_name']").val(),
                due_date: $("input[name='due_date']").val(),
                payment_amount: $("input[name='payment_amount']").val(),
                booking_id      : $("#booking_id").val(),
                payment_date    : innGrid._getBaseFormattedDate($("input[name='payment_date']").val()),
                payment_type_id : $("select[name='payment_type_id']").val(),
                customer_id     : $("select[name='customer_id']").val(),
                payment_amount  : $("input[name='payment_amount']").val()
            },
            success: function (resp) { 
                console.log('resp',resp);
                if(resp.success){

                    modalContent = '<div class="modal fade" id="payment_link_modal">'+
                                        '<div class="modal-dialog" role="document">'+
                                            '<div class="modal-content">'+
                                                '<div class="modal-header">'+
                                                    '<h5 class="modal-title">Payment Link</h5>'+
                                                    '<button type="button" class="close" data-dismiss="modal" aria-label="Close">'+
                                                      '<span aria-hidden="true">&times;</span>'+
                                                    '</button>'+
                                                '</div>'+
                                                '<div class="modal-body payment_link_message">'+
                                                    
                                                '</div>'+
                                                '<div class="modal-footer">'+
                                                    '<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>'+
                                                    // '<button type="button" class="btn btn-primary">Save changes</button>'+
                                                '</div>'+
                                            '</div>'+
                                        '</div>'+
                                    '</div>';
                    $('body').append(modalContent);
                    $('.payment_link_message').append('<p>Payment Link has been create successfully. Please copy this URL to pay </p>\n <b><p>'+resp.payment_link_url+'</p></b>');
                    $('#add-payment-modal').modal('hide');
                    $('#payment_link_modal').modal('show');

                    $('#payment_link_modal').on('hidden.bs.modal', function () {
                        location.reload();
                    });

                }
            }
        });
    });

$('body').on('click', '.verify_payment', function(){
    var payment_link_id = $(this).data('payment_link_id');
    var payment_id = $(this).data('payment_id');

    $(this).html("Processing. . .");
    $(this).prop("disabled", true);

    $.ajax({
            url    : getBaseURL() + 'verify_payment',
            method : 'post',
            dataType: 'json',
            data   : {
                payment_link_id : payment_link_id,
                customer_id : $("select[name='customer_id']").val(),
                payment_id : payment_id
            },
            success: function (resp) {
                console.log('resp',resp);
                if(resp.success){
                    // alert(resp.message);
                } else {
                    alert(resp.message);
                }

                location.reload();
            }
        });
});