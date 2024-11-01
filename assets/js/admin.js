jQuery( function ($) {
    $( document ).ready( function () {
        $( "button[name='unauthorize-woo-stripe']" ).on( 'click', function () {
            if(!jQuery('input[name="woocommerce_stripe_credit_card_test_mode"]:checked').val() == 1){
                var sandbox = 'no';
            } else {
                var sandbox = 'yes';
            }
            var data = {
                'action': 'csjw_deauthorize_stripe_connected_account',
                'mode': sandbox,
            };
            $.post(ajaxurl, data, function (response) {
                console.log(response);
                setTimeout("location.reload(true);", 1500);
            });
        });
        
        $( "button[name='woocommerce_stripe_credit_card_authorization']" ).on( 'click', function () {
            var data = {
                'action': 'csjw_authorization_redirect_url',
            };
            $.post(ajaxurl, data, function (response) {
                var redirect_url = response;
                if(!jQuery('input[name="woocommerce_stripe_credit_card_test_mode"]:checked').val() == 1){
                    var sandbox = 'no';
                } else {
                    var sandbox = 'yes';
                }
                var link = redirect_url + '&sandbox=' + sandbox ;
                window.location.replace(link);
            });    
        });
    });
});

