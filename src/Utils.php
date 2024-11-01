<?php
namespace ShoppingOS;

class Utils {

	/**
	 * Set current refund for further processing
	 *
	 * Set coresponding session variable to reflect that redirect to bank
	 * is awaited.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function set_redirecting_to_bank_refund( $refund_id, $order_id ) {
		if ( ! isset( \WC()->session ) ) {
			\WC()->initialize_session();
		}
		\WC()->session->set( 'doing_refund', [
			'order_id'  => $order_id,
			'refund_id' => $refund_id,
		] );
	}

	/**
	 * Unset current refund after processing
	 *
	 * Unset coresponding session variable to reflect the redirect to bank
	 * has been already performed.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function unset_redirecting_to_bank_refund() {
		if ( ! isset( \WC()->session ) ) {
			\WC()->initialize_session();
		}
		\WC()->session->set( 'doing_refund', false );
	}

	/**
	 * Check whether there is a refund to process
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function is_redirecting_to_bank_refund(): array {
		if ( ! isset( \WC()->session ) ) {
			\WC()->initialize_session();
		}
		$refund = \WC()->session->get( 'doing_refund' );

		return is_array( $refund ) ? [ $refund['refund_id'], $refund['order_id'] ] : [];
	}

	/**
	 * Set current refund to be finished processing on WooCommerce side
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function set_returning_to_wc_refund( $refund_id, $order_id ) {
		if ( ! isset( \WC()->session ) ) {
			\WC()->initialize_session();
		}
		\WC()->session->set( 'doing_refund_bank_confirmation', [
			'order_id'  => $order_id,
			'refund_id' => $refund_id,
		] );
	}

	/**
	 * Unset current refund after processing is finished
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function unset_returning_to_wc_refund() {
		if ( ! isset( \WC()->session ) ) {
			\WC()->initialize_session();
		}
		\WC()->session->set( 'doing_refund_bank_confirmation', false );
	}

	/**
	 * Check whether there is a refund to process on WooCommerce side
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function is_returning_to_wc_refund(): array {
		if ( ! isset( \WC()->session ) ) {
			\WC()->initialize_session();
		}
		$refund = \WC()->session->get( 'doing_refund_bank_confirmation' );

		return is_array( $refund ) ? [ $refund['refund_id'], $refund['order_id'] ] : [];
	}

	/**
	 * Add order note about successful refund
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function add_order_note_successfull_refund( $order_id, $refund_id, $additional_info = '' ) {

		$order = \wc_get_order( $order_id );
		$refund = \wc_get_order( $refund_id );

		$order_note     = [];
		$order_note[]   = sprintf( 'Successfully refunded %s (#%s)', sprintf( \get_woocommerce_price_format(), \get_woocommerce_currency_symbol(), $refund->get_amount() ), $refund->get_id() );

		if ( ! empty( $additional_info ) ) {
			$order_note[] = $additional_info;
		}

		$order_note_str = implode( PHP_EOL, $order_note );

		$order->add_order_note( $order_note_str );
	}

	/**
	 * Check whether it's an order details page
	 *
	 * Example: /wp-admin/post.php?post=125&action=edit
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function is_edit_shop_order_page(): bool {
		global $pagenow;

		$is_order_details = 'post.php' === $pagenow && isset( $_GET['post'] ) && 'shop_order' === get_post_type( $_GET['post'] );

		return $is_order_details;
	}

	/**
	 * Adds admin notice
	 *
	 * @since 2.0.1
	 * @return void
	 */
	public static function add_admin_notice( string $message, string $class = 'warning', bool $is_dismissible = true ): void {
		if ( $class !== 'error' && $class !== 'warning' && $class !== 'success' && $class !== 'info' ) {
			$class = 'warning';
		}

		$_class = sprintf( 'notice notice-%s', $class );
		$_class .= $is_dismissible ? ' is-dismissible' : '';

		add_action( 'admin_notices', function () use ( $_class, $message ) {
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $_class ), esc_html( $message ) );
		} );
	}
}
