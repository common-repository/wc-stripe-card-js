<?php
/**
 * Plugin Name: Card Stripe js For WooCommerce
 * Plugin URI: https://wpexperts.io/
 * Description: Take credit card payments on your store using Stripe.
 * Author: WPExperts
 * Author URI: https://www.wpexperts.io/
 * Version: 1.1
 * Tested up to: 6.0
 * Text Domain: woo-stripe
 * Domain Path: /languages
 */
if (!defined('ABSPATH')) {
    exit;
}

add_action( 'plugins_loaded', 'csjw_wc_stripe_load' );
/**
 * Add notice if woocommerce is missing
 */

function csjw_woocommerce_is_missing_notice() {
    echo '<div class="error"><p><strong>' . sprintf(esc_html__('Stripe requires WooCommerce to be installed and active. ', 'woo-stripe')) . '</strong></p></div>';
}

/**
 * Load plugin files  
 */

function csjw_wc_stripe_load() {
    if ( ! class_exists( 'woocommerce' ) ) {
        add_action( 'admin_notices', 'csjw_woocommerce_is_missing_notice' );
        return;
    }
    load_plugin_textdomain( 'woo-stripe', false, plugin_basename( dirname( __FILE__ ) ) . 'languages' );
    if ( ! defined( 'CSJW_WOO_STRIPE_PLUGIN_PATH' ) ) {
        define( 'CSJW_WOO_STRIPE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
    }
    if ( ! defined( 'CSJW_WOO_STRIPE_PLUGIN_URL' ) ) {
        define( 'CSJW_WOO_STRIPE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
    }
    if ( ! class_exists( 'Csjw_Woo_Stripe' ) ) :

        class Csjw_Woo_Stripe {
            /**
             * @var Singleton The reference the *Singleton* instance of this class
             */
            private static $instance;

            /*
             * singelton
             */

            public static function get_instance() {
                if ( null === self::$instance ) {
                    self::$instance = new self();
                }
                return self::$instance;
            }

            private function __clone() {
                
            }

            private function __wakeup() {
                
            }
            /**
             * Private constructor 
             */
            private function __construct() {
                if( ! defined( 'CSJW_WC_STRIPE_MID_SERVER_URL' ) ){
                    define( 'CSJW_WC_STRIPE_MID_SERVER_URL' ,esc_url('https://wordpress-237898-1297316.cloudwaysapps.com' ));
                }
                if ( ! defined( 'CSJW_WOO_STRIPE_PLUGIN_PATH' ) ) {
                    define( 'CSJW_WOO_STRIPE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
                } 
                if ( ! defined( 'CSJW_WOO_STRIPE_PLUGIN_URL' ) ) {
                    define( 'CSJW_WOO_STRIPE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
                }
                add_action( 'admin_init', array( $this, 'install') );
                $this->init();
            }

            public function install() {}
            /**
             * Init plugin files
             */
            public function init() {
                require_once CSJW_WOO_STRIPE_PLUGIN_PATH . 'includes/stripe-api/class-woo-stripe-helper.php';
                require_once CSJW_WOO_STRIPE_PLUGIN_PATH . 'includes/stripe-api/class-woo-stripe-api.php';
                require_once CSJW_WOO_STRIPE_PLUGIN_PATH . 'includes/class-wc-gateway-stripe.php';

                $csjw_ajax_auth = new Csjw_Woo_Gateway_Stripe;
                add_action( 'wp_ajax_csjw_authorization_redirect_url', array( $csjw_ajax_auth, 'csjw_authorization_redirect_url' ));

                add_filter( 'woocommerce_payment_gateways', array( $this, 'csjw_add_wc_stripe_gateway' ) );
//                add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'plugin_action_links'));
                add_action( 'admin_init', array( 'Csjw_Woo_Gateway_Stripe', 'csjw_get_code_stripe_authorization' ) );
                add_action( 'wp_ajax_csjw_deauthorize_stripe_connected_account', array( 'Csjw_Woo_Gateway_Stripe', 'csjw_deauthorize_stripe_connected_account' ) );
                add_action( 'wp_ajax_nopriv_csjw_deauthorize_stripe_connected_account', array( 'Csjw_Woo_Gateway_Stripe', 'csjw_deauthorize_stripe_connected_account' ) );
                add_action( 'admin_notices',array( 'Csjw_Woo_Gateway_Stripe', 'csjw_display_stripe_connect_notice')) ;
            }
            /**
             * Add gateway to woocommerce
             * @param array $methods
             * 
             */
            public function csjw_add_wc_stripe_gateway( $methods ) {
                $methods[] = 'Csjw_Woo_Gateway_Stripe';
                return $methods;
            }

        }

        Csjw_Woo_Stripe::get_instance();
    endif;
}



