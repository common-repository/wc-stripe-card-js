/* global wc_stripe_params */
jQuery(function ($) {
    'use strict';
    $( document ).ready( function () {
        try {
            Stripe.setPublishableKey(csjw_woo_stripe_params.key);
        } catch (error) {
            console.log(error);
            return;
        }
        var stripe_credit_card_number, stripe_expiry, stripe_cvc,card;
        stripe_credit_card_number = '#stripe_credit_card-card-number';
        stripe_expiry = '#stripe_credit_card-card-expiry';
        stripe_cvc = '#stripe_credit_card-card-cvc';
        // Use card js 
        setTimeout(function () {
             card = new Card({
                form: document.querySelector( 'form.woocommerce-checkout' ),
                container: '.card-wrapper',
                formSelectors: {
                    numberInput: stripe_credit_card_number,
                    expiryInput: stripe_expiry,
                    cvcInput: stripe_cvc,
                },

            });
        }, 3000);
    
        $( 'form.woocommerce-checkout' ).on( 'checkout_place_order', onSubmitStripe );
   
        $( 'form.woocommerce-checkout' ).on( 'change', resetFields );
        function resetFields() {
            $( '.woo-stripe-source-error, .woo-stripe-source' ).remove();
        }
        function onSubmitStripe( event ) {
            
            if($(":input#payment_method_stripe_credit_card").is(":checked")) {
                var exp, errorContainer, token_data;
                event.preventDefault();
                if ( form_has_token() ) {
                    return true;
                }
                exp = $( stripe_expiry ).payment( 'cardExpiryVal' );
                errorContainer = $( '.woo-stripe-source-errors' );
    
                /*creating token!
                 * create token on stripe
                 */
                try {
                     token_data = {
                        number: $( stripe_credit_card_number ).val(),
                        cvc: $( stripe_cvc ).val(),
                        exp_month: exp.month,
                        exp_year: exp.year
                    }
                    // Send address if it's there
                    if ($('#billing_address_1').length != 0) {
                        token_data.address_line1 = $('#billing_address_1').val();
                    }
                    if ($('#billing_address_2').length != 0) {
                        token_data.address_line2 = $('#billing_address_2').val();
                    }
                    if ($('#billing_city').length != 0) {
                        token_data.address_city = $('#billing_city').val();
                    }
                    if ($('#billing_state').length != 0) {
                        token_data.address_state = $('#billing_state').val();
                    }
                    if ($('#billing_postcode').length != 0) {
                        token_data.address_zip = $('#billing_postcode').val();
                    }
    
                    // Create token on Stripe
                    Stripe.card.createToken( token_data, function ( status, response ) {
                        
    
                        if ( response.error ) {
                            $( errorContainer ).html( '<ul class="woocommerce_error woocommerce-error wc-stripe-error"><li /></ul>' );
                            $( errorContainer ).find( 'li' ).html( '<p>' + response.error.message + '</p>' ); // Prevent XSS
    
                            if ( $( '.wc-stripe-error').length ) {
                                $('html, body').animate({
                                    scrollTop: ($( '.wc-stripe-error' ).offset().top - 200)
                                }, 200);
                            }
                        } else {
                            $( 'form.woocommerce-checkout' ).append(
                                    $( '<input type="hidden" /> ')
                                    .addClass( 'woo-stripe-source' )
                                    .attr( 'name', 'woo_stripe_source' )
                                    .val( response.id )
                                    );
                            if ( $( 'form#add_payment_method' ).length) {
                                $( 'form.woocommerce-checkout' ).off( 'submit', function () {
                                    return false;
                                });
                            }
                            $( 'form.woocommerce-checkout' ).submit();
                        }
                        return false; // submit from callback
    
                    });
                    return false;
                } catch ( error ) {
                    $( errorContainer ).html( '<ul class="woocommerce_error woocommerce-error wc-stripe-error"><li /></ul>' );
                    $( errorContainer ).find( 'li' ).html( '<p>' + error.message + '</p>' ); // Prevent XSS
    
                    return false;
                }
    //            }
                return false;
    
            }
        }

        function form_has_token() {
            if ($('input[name="woo_stripe_source"]').val()) {
                return true;
            } else {
                return false;
            }
        }


    });

});