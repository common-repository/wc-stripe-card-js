<?php
/**
 * 
 * Csjw_Woo_Gateway_Stripe class.
 *
 * @extends WC_Payment_Gateway_CC
 */
class Csjw_Woo_Gateway_Stripe extends WC_Payment_Gateway_CC {
    public function __construct() {
        $this->id = 'stripe_credit_card';
        $this->method_title = __('Stripe Credit Card');
        $this->method_desciption = __('stripe adds payment field to checkout');
        $this->has_fields = true;
        $this->supports = array(
            'products',
            'refunds'
        );
        // Load the form fields.
        $this->init_form_fields();
        // Load the settings.
        $this->init_settings();
        
        // Get setting values.
        $this->title = $this->get_option( 'title' );
        $this->description = $this->get_option( 'description' );
        $this->enabled = $this->get_option( 'enabled' );
        $this->test_mode = 'yes' === $this->get_option( 'test_mode' );
        // Used Hooks
        add_action( "woocommerce_update_options_payment_gateways_{$this->id}", array($this, 'process_admin_options' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'csjw_stripe_payment_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'csjw_enqueue_stripe_admin_scripts' ) );
        add_action( 'woocommerce_api_'.strtolower( get_class($this) ), array( $this, 'csjw_check_for_webhook' ) );
    }
    /*
    *  Initialize plugin settings form fields.
    *  @since 1.0.0 
    */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __( 'Enable/Disable', 'woo-stripe' ),
                'label' => __( 'Enable Stripe', 'woo-stripe' ),
                'type' => 'checkbox',
                'default' => 'no'
            ),
            'title' => array(
                'title' => __( 'Title', 'woo-stripe' ),
                'type' => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'woo-stripe' ),
                'default' => __( 'Stripe (card-js)', 'woo-stripe' ),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __( 'Description', 'woo-stripe' ),
                'type' => 'textarea',
                'desc_tip' => true,
                'description' => __( 'This controls the description which the user sees during checkout.', 'woo-stripe' ),
                'default' => __( 'Pay using Stripe Cardjs', 'woo-stripe' ),
            ),
            'test_mode' => array(
                'title' => __( 'Test mode', 'woo-stripe' ),
                'label' => __( 'Enable Test Mode', 'woo-stripe' ),
                'type' => 'checkbox',
                'description' => __( 'Place the payment gateway in test mode using test API keys.', 'woo-stripe' ),
                'default' => 'yes',
                'desc_tip' => true,
            ),
            'authorization' => array(
                'type' => 'button',
            ),

        );
    }

    /**
     *  Payment Form on checkout page
     *  @since 1.0.0
     */
    public function payment_fields() {
        $description = $this->get_description();
        $description = ! empty( $description ) ? $description : '';
        ob_start();
        if ( $this->test_mode ) {
            $description .= ' ' . sprintf( __( 'TEST MODE ENABLED. In test mode, you can use the card number 4242424242424242 with any CVC and a valid expiration date or check the <a href="%s" target="_blank">Testing Stripe documentation</a> for more card numbers.', 'woo-stripe'), 'https://stripe.com/docs/testing' );
        }
        $description = trim( $description );
        echo wpautop( wp_kses_post( $description ) );
        echo '<div class="card-wrapper"></div>';
        echo' <div class="woo-stripe-source-errors" role="alert"></div>';
        $this->form();
        ob_end_flush();
    }
    /**
     * Load checkout form scripts 
     * @since 1.0.0
     */
    public function csjw_stripe_payment_scripts() { 
        if (  ! is_product() && ! is_cart() && ! is_checkout() ) {
            return;
        }
        if ( 'no' === $this->enabled ) {
            return;
        }
        if ( ! $this->is_available() ) {
            return;
        }
        wp_enqueue_script( 'jquery-payment' );
        wp_enqueue_script( 'wc-credit-card-form' );
        $stripe_params = array(
            'key' => Csjw_Woo_stripe_Api::csjw_get_publish_key(),
        );
      
        wp_register_script( 'woo-stripe', 'https://js.stripe.com/v2/', '', false, true);
        wp_enqueue_script( 'woo-stripe-cardjs-script', CSJW_WOO_STRIPE_PLUGIN_URL . 'assets/js/card.js', array('jquery'), false, false);

        wp_register_script( 'csjw_woo-stripe-scripts', CSJW_WOO_STRIPE_PLUGIN_URL . 'assets/js/stripe.js', array('jquery-payment', 'woo-stripe', 'woo-stripe-cardjs-script' ), false, true );
        wp_localize_script( 'csjw_woo-stripe-scripts', 'csjw_woo_stripe_params', $stripe_params);
        wp_enqueue_script( 'csjw_woo-stripe-scripts' );
    }

    /**
     * Enqueue scripts in admin dashboard
     */

    public function csjw_enqueue_stripe_admin_scripts() {
        wp_enqueue_script( 'woo-stripe-admin-script', CSJW_WOO_STRIPE_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), false, false );
    }

    /**
     * Check if there is connected stripe account 
     */

    public function csjw_is_stripe_user_id_exists() {
        $is_user_id_set = ( get_option( 'stripe_user_id', false ) ? true : false );
        return $is_user_id_set;
    }
    /**
     * Make payment method unavailable if there is no stripe connected account
     * @since 1.0.0 
     */
    public function is_available() {
        $is_available = ( $this->csjw_is_stripe_user_id_exists() ? true : false );
        return $is_available;
    }

    /**
     * generate Authorization button in backend
     */

    public function generate_button_html($key, $data) {
        $field = $this->plugin_id . $this->id . '_' . $key;
        $defaults = array(
            'class' =>  'button-secondary',
            'css'   =>     '',
            'custom_attributes' => array(
                // 'onclick'   => "location.href='" . $this->csjw_authorization_redirect_url() . "'",
                ),
            'desc_tip'  =>  false,
            'description'   =>  '',
            'title' =>  'Authorization',
        );

        $data = wp_parse_args( $data, $defaults );

        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
                <?php echo $this->get_tooltip_html( $data ); ?>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>

                    <?php if ( get_option( 'stripe_user_id' , false ) ) { ?>
                            <button class="<?php echo esc_attr( $data['class'] ); ?>" type="button" name="unauthorize-woo-stripe" id="unauthorize-woo-stripe" value="unauthorized" style="<?php echo esc_attr( $data['css'] ); ?>"  > <?php echo __('unauthorize') ?></button>

                    <?php } else { ?>
                            <button class="<?php echo esc_attr( $data['class'] ); ?>" type="button" name="<?php echo esc_attr( $field ); ?>" id="<?php echo esc_attr( $field ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php echo $this->get_custom_attribute_html( $data ); ?> ><?php echo wp_kses_post( $data['title'] ); ?></button>

                    <?php  } ?>
                    <?php echo $this->get_description_html( $data ); ?>
                </fieldset>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }
    /**
     *  Redirect to stripe to get code
     */
    public function csjw_authorization_redirect_url() {
        $site_url = get_admin_url() . 'admin.php';
        $client_id = Csjw_Woo_stripe_Api::csjw_get_client_id(); //platform client id
        $redirect_url = CSJW_WC_STRIPE_MID_SERVER_URL . "?client_id=" . $client_id . '&site_url=' . $site_url;
        wp_die($redirect_url);
        }
    /**
     * Get stripe user id from code 
     */
    public static function csjw_get_code_stripe_authorization() {
        if ( isset( $_GET['code'] ) ) {
            $request = array(
                'code'  =>  sanitize_text_field($_GET['code']),
                'grant_type'    =>  'authorization_code',
                'client_secret' =>  Csjw_Woo_stripe_Api::csjw_get_secret_key()
                );
         $auth = Csjw_Woo_stripe_Api::csjw_request_auth( $request, $api = 'oauth/token');
         
            if ( $auth->stripe_user_id ) {
                update_option( 'stripe_user_id', $auth->stripe_user_id, true );
				delete_option('woo_stripe_auth_error_notice');
            }

            wp_redirect( get_admin_url() . "admin.php?page=wc-settings&tab=checkout&section=stripe_credit_card" );
            exit;
        }
    }
    /**
     * Deauthorize stripe account
     */
    public static function csjw_deauthorize_stripe_connected_account() {
        $options = get_option( 'woocommerce_stripe_credit_card_settings' );
            $secret_key = ($_POST['mode'] == yes ? $options['test_secret_key'] : $options['live_secret_key'] );
            $client_id = ($_POST['mode'] == yes ? $options['test_client_id'] : $options['live_client_id'] );

        $request = array(
            'client_id' => $client_id,
            'stripe_user_id' => get_option('stripe_user_id', false),
            'client_secret' => $secret_key
            );
        $deauth = Csjw_Woo_stripe_Api::csjw_request_auth( $request, $api = 'oauth/deauthorize');
        if( isset( $deauth->stripe_user_id ) ){
        delete_option( 'stripe_user_id' );
        delete_option('woo_stripe_auth_error_notice');

        }else{          
            if(isset($deauth->error_description)){
				delete_option( 'stripe_user_id' );
                update_option('woo_stripe_auth_error_notice',$deauth->error_description,true);
            }
        }
        wp_die();
    }
    /**
     * Process Payment
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        $source = sanitize_text_field($_POST['woo_stripe_source']);
        if ( $source ) {
            $request_params = array(
                'amount' => Csjw_Woo_Stripe_Helper::csjw_get_stripe_amount( $order->get_total() ),
                'currency' => $order->get_currency(),
                'description' => sprintf( __( '%1$s - Order %2$s', 'woo-stripe'), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES), $order->get_order_number() ),
                'source' => $source,
                'metadata' => array(
                    __( 'Customer Name', 'woo-stripe' ) => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                    __( 'Customer Email', 'woo-stripe' ) => $order->get_billing_email(),
                    __( 'Order ID ', 'woo-stripe'  ) => $order->get_order_number(),
                     __( 'site_url', 'woo-stripe'  ) => add_query_arg('wc-api', strtolower(get_class($this) ), trailingslashit(get_home_url()))

                )
            );            
            $charge = Csjw_Woo_stripe_Api::csjw_request_auth($request_params, 'charges');  
            if ( isset( $charge->id )) { 
                update_post_meta( $order_id, '_transaction_id', $charge->id ) ;
                $order->update_status( 'processing', sprintf( __( 'Stripe charge payment: %s.', 'woo-stripe' ), $charge->id  ) );
                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order),
                );
            } else {
                wc_add_notice( $charge->error->message, 'error' );
                $order->add_order_note( __( $charge->error->message, 'woo-stripe' ) );

                return array(
                    'result' => 'fail',
                    'redirect' => '',
                );
            }
        } else {
            return array(
                'result' => 'fail',
                'redirect' => '',
            );
        }
    }

    /**
     * Check webhooks stripe events
     */
    public function csjw_check_for_webhook() {
        $request_body    = file_get_contents( 'php://input' );
        $notification = json_decode( $request_body );
        switch ( $notification->type ) {

            case 'charge.succeeded':
            case 'charge.captured':
                $this->csjw_process_webhook_charge_succeeded( $notification );
                break;

            case 'charge.failed':
                $this->csjw_process_webhook_charge_failed( $notification );
                break;

            case 'charge.refunded':
                $this->csjw_process_webhook_charge_refund( $notification );
                break;
        }
    }
    /**
     * Handle stripe charge.succeeded event
     * @param object $notification
     * 
     */
    public function csjw_process_webhook_charge_succeeded( $notification ) {
		$order = Csjw_Woo_Stripe_Helper::csjw_get_order_by_charge_id( $notification->data->object->id );               
		if ( 'processing' !== $order->get_status() ) {
                    return;
		}
               $mode = $this->get_option( 'test_mode' );
               if  (!$mode == 'yes')
               {
                   $sandbox = 'via sandbox';
               } else {
                   $sandbox = '';
               }
               $order->payment_complete( $notification->data->object->id );
               $order->add_order_note( sprintf( __( 'Stripe charge complete ' . ''  . $sandbox . ' (Charge ID: %s)', 'woocommerce-gateway-stripe' ), $notification->data->object->id ) );

    }
    /**
     * Handle stripe refund if order is refunded from stripe dashboard
     * @param object $notification
     */
    public function csjw_process_webhook_charge_refund($notification){
            $order = Csjw_Woo_Stripe_Helper::csjw_get_order_by_charge_id( $notification->data->object->id );
            $order_id =$order->get_id();
            $reason = __( 'Refunded via Stripe Dashboard', 'woo-stripe' );

            $refund = wc_create_refund(
                            array(
                                    'order_id' => $order_id,
                                    'amount'   => $this->csjw_get_stripe_refund_amount( $notification ),
                                    'reason'   => $reason,
                            )
            );
            				
            $order->add_order_note( $reason );

    }
    public function csjw_get_stripe_refund_amount( $notification ) {
            if ( $this->is_partial_capture( $notification ) ) {
                    $amount = $notification->data->object->refunds->data[0]->amount / 100;

                    if ( in_array( strtolower( $notification->data->object->currency ), Csjw_Woo_Stripe_Helper::csjw_no_decimal_currencies() ) ) {
                            $amount = $notification->data->object->refunds->data[0]->amount;
                    }

                    return $amount;
            }

            return false;
    }
    /**
     * Handle stripe event charge.failed
     * @param object $notification
     * 
     */
    public function csjw_process_webhook_charge_failed($notification){
        	$order = Csjw_Woo_Stripe_Helper::csjw_get_order_by_charge_id( $notification->data->object->id );

		// If order status is already in failed status don't continue.
		if ( 'failed' === $order->get_status() ) {
			return;
		}
                $order->update_status( 'failed', __( 'This payment failed to clear.', 'woo-stripe' ) );

    }
    /**
     * Display notice if trying to disconnect stripe account failed
     */
    public static function csjw_display_stripe_connect_notice(){
        $connect_error = get_option('woo_stripe_auth_error_notice',false);
        if( $connect_error ){
            $class = 'notice notice-error';
            $message = $connect_error ;
            printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
        }
    }
    /**
     * Refund order
     * @param int $order_id
     * @param float $amount
     * @param string $reason
     * @return boolean
     */
    public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return false;
		}

		$request = array();

                $order_currency = $order->get_currency();
                $charge_id      = get_post_meta( $order_id, '_transaction_id', true );

		if ( ! $charge_id ) {
			return false;
		}

		if ( ! is_null( $amount ) ) {
			$request['amount'] = Csjw_Woo_Stripe_Helper::csjw_get_stripe_amount( $amount, $order_currency );
		}

		if ( $reason ) {
			$request['metadata'] = array(
				'reason' => $reason,
			);
		}
		$request['charge'] = $charge_id;
                $response = Csjw_Woo_stripe_Api::csjw_request_auth( $request, 'refunds' );
                if( $response->error ){
                 $order->add_order_note( __("failed to refund : ".$response->error->message) );
                    return false;
                }elseif( isset( $response->object )&& 'refund' == $response->object && isset( $response->status ) && 'succeeded' == $response->status){
                    
                   $order->add_order_note( __( "Refunded : ".$response->charge ) );
                   return true;
                }
                else{
                    return false;
                }
	}

}
?>
