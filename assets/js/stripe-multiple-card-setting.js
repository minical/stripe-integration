var stripeCardGateway = false;

document.addEventListener("post.open_card_model", function (e) {
    console.log('e', e);
    console.log('e.detail', e.detail);

    const customerId = e?.detail?.customer_id;
    const cardId = e?.detail?.card_id;

    const isFormFieldsEmpty = (
        !$('#cc_number').val() &&
        !$('input[name="cc_expiry"]').val() &&
        !$('input[name="cvc"]').val()
        );

    if(
        innGrid.featureSettings.stripePublicKey !== '' &&
        innGrid.featureSettings.stripePublicKey !== 'stripe'
    ){

        if ((customerId && typeof cardId === 'undefined') || isFormFieldsEmpty) {

            $('.credit_card_field').hide();

            const stripeButtonHtml = `
                <div class="form-group stripe_multiple_card_button">
                    <label for="stripe_card_data" class="col-sm-4">Card Details</label>
                    <div class="col-sm-8">
                        <button type="button" class="btn btn-info strp_card_btn" onclick="show_card_iframe()">Add Card Details</button>
                    </div>
                </div>
            `;

            $('#card_name').parent('.form-group').after(stripeButtonHtml);
        } else {
            $('.credit_card_field').show();
        }

        setTimeout(function () {
            $('.strp_card_btn').trigger('click');
        }, 500);
    }
});


async function show_card_iframe() {
    if ($('#multiple-card-element').length > 0) return; // Prevent duplicate iframe

    const stripeCardIframe = `
        <div class="col-sm-12">
            <div id="payment-form">
                <div id="payment-status-container" style="margin-bottom: 10px;"></div>

                <input type="hidden" id="stripe-card-token" name="stripe_card_token" />
                <input type="hidden" id="stripe-card-exp_month" name="stripe_card_exp_month" />
                <input type="hidden" id="stripe-card-exp_year" name="stripe_card_exp_year" />
                <input type="hidden" id="stripe-card-lastfour" name="stripe_card_lastfour" />

                <div id="multiple-card-element" style="padding: 12px; border: 1px solid #ccc; border-radius: 6px; margin-bottom: 12px;"></div>
                <button style="display:none;" id="multiple-card-button" type="button" class="btn btn-success">Create Token</button>
            </div>
        </div>
    `;

    $('.stripe_multiple_card_button').append(stripeCardIframe);
    $('.strp_card_btn').parent('div').remove();

    stripeCardGateway = true;

    const stripeCard = Stripe(innGrid.featureSettings.stripePublicKey);
    const cardElements = stripeCard.elements();

    const style = {
        base: {
            fontSize: '16px',
            color: '#32325d',
            fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
            '::placeholder': { color: '#aab7c4' }
        },
        invalid: {
            color: '#fa755a',
            iconColor: '#fa755a'
        }
    };

    // const multipleCard = cardElements.create('card', { style: style });
    const multipleCard = cardElements.create('card', { style: style, hidePostalCode: true });
    multipleCard.mount('#multiple-card-element');

    const multipleCardButton = document.getElementById('multiple-card-button');
    multipleCardButton.addEventListener('click', async () => {
        const statusContainer = document.getElementById('payment-status-container');

        try {
            const result = await stripeCard.createToken(multipleCard);
            if (result.error) {
                console.error('Token error:', result.error);
                statusContainer.innerHTML = "Please enter valid card details.";
                statusContainer.style.color = "red";
                $("#save_card").prop('disabled', false);
                return;
            } else {
                console.log('Token created:', result.token);

                statusContainer.style.color = "black";

                statusContainer.innerHTML = "Token created successfully";

                document.getElementById('stripe-card-token').value = result.token.id;
                document.getElementById('stripe-card-exp_month').value = result.token.card.exp_month;
                document.getElementById('stripe-card-exp_year').value = result.token.card.exp_year;
                document.getElementById('stripe-card-lastfour').value = result.token.card.last4;
            }
        } catch (e) {
            console.error('Token creation exception:', e);
            statusContainer.innerHTML = "Payment Failed";
        }
    });

    // Optional: auto trigger token creation after mount (if needed)
    // multipleCardButton.click();
}
