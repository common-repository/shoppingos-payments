<?php
namespace ShoppingOS;

use ShoppingOS\Traits\Singleton;

/**
 * Checkout class
 *
 * Modifies checkout page
 *
 * @since   2.0.0
 * @package ShoppingOS
 */
class Checkout {
	use Singleton;

	/**
	 * Class constructor
	 *
	 * @since 2.0.0
	 * @return void
	 */
	protected function __construct() {
		add_action( 'woocommerce_after_order_notes', [ $this, 'sos_selected_bank_field' ] );
		add_action( 'woocommerce_checkout_update_order_meta', [ $this, 'save_selected_bank_hidden_field' ] );
	}

	/**
	 * Displays the hidden field for the selected bank during checkout
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function sos_selected_bank_field() {
		echo '<input id="bank-value" type="hidden" name="sos_selected_bank" value="">';
	}

	/**
	 * Saving the hidden field value in the order metadata
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function save_selected_bank_hidden_field( $order_id ) {
		$selected_bank_meta = get_post_meta( $order_id, 'sos_selected_bank', true );

		if ( isset( $_POST['sos_selected_bank'] ) ) {
			update_post_meta( $order_id, 'sos_selected_bank', sanitize_text_field( $_POST['sos_selected_bank'] ) );
		} elseif ( isset( $selected_bank_meta ) ) {
			delete_post_meta( $order_id, 'sos_selected_bank', $selected_bank_meta );
		}
	}
}
