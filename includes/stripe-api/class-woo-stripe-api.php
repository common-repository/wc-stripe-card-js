<?php

if (!defined('ABSPATH')) {
    exit;
}       
// require_once CSJW_WOO_STRIPE_PLUGIN_PATH . 'includes/class-wc-gateway-stripe.php';
class Csjw_Woo_stripe_Api {
    const URL = 'https://api.stripe.com/v1/';
    const CONNECT_URL = 'https://connect.stripe.com/';
    /**
     * get secret key based on test_mode value
     * @return string
     */
    public $api_keys;

    public static function csjw_get_secret_key() {
        $options = get_option( 'woocommerce_stripe_credit_card_settings' );
        if ( isset( $options['test_mode'] ) ) {
            $secret_key = ('yes' == $options['test_mode'] ? $options['test_secret_key'] : $options['live_secret_key'] );
            return $secret_key;
        } 
    }
    /**
     * Get publish key based on test_mode value
     * @return string
     */
    public static function csjw_get_publish_key() {
        $options = get_option( 'woocommerce_stripe_credit_card_settings' );
		
        if ( isset( $options['test_mode'] ) ) {
            $publish_key = ( 'yes' == $options['test_mode'] ? $options['test_publish_key'] : $options['live_publish_key'] );
            return $publish_key;
        } 
    }
    /**
     * Get client id based on test_mode value
     * @return type
     */
    public static function csjw_get_client_id() {
        $options = get_option('woocommerce_stripe_credit_card_settings');
        if ( isset( $options['test_mode'] ) ) {
            $client_id = ('yes' == $options['test_mode'] ? $options['test_client_id'] : $options['live_client_id'] );
            return $client_id;
        }
    }
    /**
     * Get Headers to be sent in stripe api requests
     * @return array
     */
    public static function get_headers() {
            $headers = array();
            $stripe_user_id=get_option( 'stripe_user_id', false );
            if( $stripe_user_id){
                 $headers[] = 'Stripe-Account:'.get_option( 'stripe_user_id', false );
            }
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        return $headers;
    }
  
    /**
     * Send auth/deauth requests
     * @param array $request
     * @param string $api
     * @return object
     */
    public static function csjw_request_auth( $request, $api ){

       $ch = curl_init();
        if ( 'oauth/token'== $api || 'oauth/deauthorize' == $api ) {
            $url = self::CONNECT_URL.$api;
        } else {
            $headers = self::get_headers();
            $url = self::URL.$api;
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_POST, 1);
        curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $request, true ) );
        curl_setopt( $ch, CURLOPT_USERPWD, Csjw_Woo_stripe_Api::csjw_get_secret_key() . ':' . '' );
        $result = curl_exec( $ch );
        if(isset($_REQUEST['test_client_id']) && isset($_REQUEST['test_secret_key']) && isset($_REQUEST['test_publish_key']) 
            && isset($_REQUEST['live_client_id']) && isset($_REQUEST['live_secret_key']) && isset($_REQUEST['live_publish_key'])) {
            // Sandbox keys
            $test_client_id = sanitize_text_field($_REQUEST['test_client_id']);
            $test_secret_key = sanitize_text_field($_REQUEST['test_secret_key']);
            $test_publish_key = sanitize_text_field($_REQUEST['test_publish_key']);
            // Live keys
            $live_client_id = sanitize_text_field($_REQUEST['live_client_id']);
            $live_secret_key = sanitize_text_field($_REQUEST['live_secret_key']);
            $live_publish_key = sanitize_text_field($_REQUEST['live_publish_key']);
            $options = array();
            $options = get_option('woocommerce_stripe_credit_card_settings');
            $options['live_client_id']   = $live_client_id;
            $options['test_client_id']   = $test_client_id;
            $options['live_secret_key']  = $live_secret_key;
            $options['test_secret_key']  = $test_secret_key;
            $options['live_publish_key'] = $live_publish_key;
            $options['test_publish_key'] = $test_publish_key;
			
            update_option('woocommerce_stripe_credit_card_settings',$options);
        }
        $result = json_decode( $result );
        curl_close( $ch );
        return $result;
    }

}
