<?php

class Csjw_Woo_Stripe_Helper{
        /**
         * Strpe amount in cents
         * @param float $total
         * @param string $currency
         * @return float|int
         */
        public static function csjw_get_stripe_amount( $total, $currency = '' ) {
		if ( ! $currency ) {
			$currency = get_woocommerce_currency();
		}
		if ( in_array( strtolower( $currency ), self::csjw_no_decimal_currencies() ) ) {
			return absint( $total );
		} else {
			return absint( wc_format_decimal( ( (float) $total * 100 ), wc_get_price_decimals() ) ); // In cents.
		}
	}
        /**
         * Get order from the charge id
         * @param string $charge_id
         * 
         */
        public static function csjw_get_order_by_charge_id( $charge_id ) {
            global $wpdb;
            
            if ( empty( $charge_id ) ) {
                    return false;
            }

            $order_id = $wpdb->get_var( $wpdb->prepare( "SELECT DISTINCT ID FROM $wpdb->posts as posts LEFT JOIN $wpdb->postmeta as meta ON posts.ID = meta.post_id WHERE meta.meta_value = %s AND meta.meta_key = %s", $charge_id, '_transaction_id' ) );

            if ( ! empty( $order_id ) ) {
                    return wc_get_order( $order_id );
            }

            return false;
	}
        /**
         * List supported currencies has no decimal in stripe
         * @return array
         */
        public static function csjw_no_decimal_currencies() {
		return array(
			'bif', // Burundian Franc
			'clp', // Chilean Peso
			'djf', // Djiboutian Franc
			'gnf', // Guinean Franc
			'jpy', // Japanese Yen
			'kmf', // Comorian Franc
			'krw', // South Korean Won
			'mga', // Malagasy Ariary
			'pyg', // Paraguayan Guaraní
			'rwf', // Rwandan Franc
			'ugx', // Ugandan Shilling
			'vnd', // Vietnamese Đồng
			'vuv', // Vanuatu Vatu
			'xaf', // Central African Cfa Franc
			'xof', // West African Cfa Franc
			'xpf', // Cfp Franc
		);
	}

        
}