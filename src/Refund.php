<?php
namespace ShoppingOS;

use ShoppingOS\Config\Plugin;
use ShoppingOS\EndpointsV1;
use ShoppingOS\Traits\SendSuccessFail;
use ShoppingOS\Utils;

/**
 * Refund class
 *
 * ShoppingOS Refund processing class
 *
 * @since   2.0.0
 * @package ShoppingOS
 */
class Refund extends \WC_Settings_API {
	use SendSuccessFail;

	/**
	 * Class constructor
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function __construct() {
		$this->plugin    = Plugin::init();
		$this->endpoints = EndpointsV1::init();
		$this->id        = 'shopping_os';

		// Define the environment of the plugin.
		$this->testmode = 'yes' === $this->get_option( 'testmode' );

		/**
		 * API credentials for ShoppingOS Service API.
		 *
		 * The credentials are obtained by the merchant after performing registration on the link below.
		 * @link https://service.shoppingos.com
		 */
		$this->app_id     = $this->get_option( 'app_id' );
		$this->app_secret = $this->get_option( 'app_secret' );

		add_action( 'admin_init', [ $this, 'maybe_proceed_to_bank_refund' ], 20, 0 );
		add_action( 'woocommerce_api_shoppingos-refund', [ $this, 'shoppingos_refund_webhook' ], 10, 0 );
		add_action( 'admin_notices', [ $this, 'refund_notice_handler' ], 1, 0 );
	}

	/**
	 * Redirects to bank to confirm refund
	 *
	 * After WooCommerce finishes native refund process, this handler
	 * redirect to bank to continue refund process on ShoppingOS side.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function maybe_proceed_to_bank_refund() {
		if ( ! Utils::is_edit_shop_order_page() ) {
			return;
		}

		$current_refund = Utils::is_redirecting_to_bank_refund();
		if ( empty( $current_refund ) ) {
			return;
		}

		list( $current_refund_id, $order_id ) = $current_refund;

		try {
			$refund = new \WC_Order_Refund( $current_refund_id );
		} catch ( \Exception $e ) {
			Utils::add_admin_notice( 'Refund does not exist or it was deleted. ' . $e->getMessage(), 'error' );
			Utils::unset_redirecting_to_bank_refund();
			return;
		}

		$current_user       = wp_get_current_user();
		$current_user_email = $current_user->user_email;
		$sos_refund_amount  = $refund->get_amount();
		$environment        = $this->testmode ? 'sandbox' : 'production';

		/**
		 * $current_user_email is sent to ShoppingOS Service
		 * in order to log the user who initiated the payment.
		 */
		$request_args = [
			'headers' => [
				'App-Id'     => $this->app_id,
				'App-Secret' => $this->app_secret,
			],
			'body' => [
				'payment_details' => [
					'environment'  => $environment,
					'order_id'     => $order_id,
					'total'        => $sos_refund_amount,
					'currency'     => \get_woocommerce_currency(),
					'psu_email'    => $current_user_email,
					'callback_url' => get_site_url() . '/?wc-api=shoppingos-refund&refund_id=' . $current_refund_id . '&order_id=' . $order_id,
					'version'      => $this->plugin->version(),
				],
			],
		];

		/**
		 * Send Payment Order request to ShoppingOS Service.
		 */
		$response = wp_remote_post( $this->endpoints->endpoint( 'refund' ), $request_args );

		if ( ! is_wp_error( $response ) ) {
			/**
			 * Storing refund details as post meta.
			 * The order item details will be added in 1.0.3
			 */
			if ( ! add_post_meta( $order_id, 'sos_refund_psu_email', $current_user_email, true ) ) {
				update_post_meta( $order_id, 'sos_refund_psu_email', $current_user_email );
			}

			if ( ! add_post_meta( $order_id, 'sos_refund_amount', $sos_refund_amount, true ) ) {
				update_post_meta( $order_id, 'sos_refund_amount', $sos_refund_amount );
			}

			if ( ! add_post_meta( $order_id, 'sos_refund_date', gmdate( 'Y-m-d H:i:s' ), true ) ) {
				update_post_meta( $order_id, 'sos_refund_date', gmdate( 'Y-m-d H:i:s' ) );
			}

			if ( ! add_post_meta( $order_id, 'sos_refund_status', 'pending', true ) ) {
				update_post_meta( $order_id, 'sos_refund_status', 'pending' );
			}

			$response_body = json_decode( $response['body'], true );

			if ( isset( $response_body['code'] ) && $response_body['code'] === 'received' ) {
				delete_post_meta( $order_id, 'sos_refund_current' );
				Utils::unset_redirecting_to_bank_refund();
				Utils::set_returning_to_wc_refund( $current_refund_id, $order_id );
				// Redirect the merchant representative for bank selection
				wp_redirect( $response_body['redirect_url'] );
				exit;
			} else {
				if ( isset( $response_body['code'] ) && $response_body['code'] === 'error' ) {
					/**
					 * @todo Consider add human notices
					 * Case #1: "Refund details are not available."
					 */
					update_post_meta( $order_id, 'sos_refund_notice', $response_body['message'] );
				} else {
					update_post_meta( $order_id, 'sos_refund_notice', 'Please contact us with the Order Id.' );
				}

				Utils::unset_redirecting_to_bank_refund();

				$refund = \wc_get_order( $current_refund_id );
				if ( $refund ) {
					$refund->delete( true );
				}
			}
		} else {
			Utils::unset_redirecting_to_bank_refund();

			$refund = \wc_get_order( $current_refund_id );
			if ( $refund ) {
				$refund->delete( true );
			}

			update_post_meta( $order_id, 'sos_refund_notice', 'Response not received, please try again.' );
		}
	}

	/**
	 * Receives the refund result
	 *
	 * After authorising and confirming the refund on bank side,
	 * the merchant representative is redirected back to admin dashboard with the result of the refund.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function shoppingos_refund_webhook(): void {
		parse_str( $_SERVER['QUERY_STRING'], $query_str );

		try {
			if ( isset( $query_str['refund_id'] ) && is_numeric( $query_str['refund_id'] ) ) {
				$refund_id = absint( $query_str['refund_id'] );
			} else {
				throw new \Exception( 'Refund ID is unset or empty' );
			}

			if ( isset( $query_str['order_id'] ) && is_numeric( $query_str['order_id'] ) ) {
				$order_id = absint( $query_str['order_id'] );
			} else {
				throw new \Exception( 'Order ID is unset or empty' );
			}
		} catch ( \Exception $e ) {
			$current_refund = Utils::is_returning_to_wc_refund();
			if ( ! empty( $current_refund ) ) {
				list( $refund_id, $order_id ) = $current_refund;

				$refund = \wc_get_order( $refund_id );
				if ( $refund ) {
					$refund->delete( true );
				}

				update_post_meta( $order_id, 'sos_refund_notice', $e->getMessage() );
				Utils::unset_returning_to_wc_refund();
				wp_redirect( get_edit_post_link( $order_id, '' ) );
			} else {
				/**
				 * @todo Followed by redirect, this notice is not going to work.
				 */
				Utils::add_admin_notice( $e->getMessage(), 'error' );
				wp_redirect( admin_url( 'edit.php?post_type=shop_order' ) );
			}
		}

		try {
			if ( isset( $query_str['message'] ) ) {
				if ( isset( $query_str['message'] ) && ! empty( $query_str['message'] ) ) {
					throw new \Exception( sprintf( '%s, please try again.', $query_str['message'] ) );
				} else {
					throw new \Exception( sprintf( 'Unknown error, please try again.' ) );
				}
			}
		} catch ( \Exception $e ) {

			$current_refund = Utils::is_returning_to_wc_refund();
			if ( ! empty( $current_refund ) ) {
				list( $refund_id, $order_id ) = $current_refund;

				$refund = \wc_get_order( $refund_id );
				if ( $refund ) {
					$refund->delete( true );
				}

				update_post_meta( $order_id, 'sos_refund_notice', $e->getMessage() );
				Utils::unset_returning_to_wc_refund();
				wp_redirect( get_edit_post_link( $order_id, '' ) );
			} else {
				Utils::add_admin_notice( $e->getMessage(), 'error' );
				wp_redirect( admin_url( 'edit.php?post_type=shop_order' ) );
			}
		}

		try {
			if ( 'shop_order' !== get_post_type( $order_id ) ) {
				throw new \Exception( "Order doesn't exist or deleted" );
			}
		} catch ( \Exception $e ) {
			/**
			 * Even though the order doesn't exist delete refund
			 */
			$refund = \wc_get_order( $refund_id );
			if ( $refund ) {
				$refund->delete( true );
			}

			//do_action( 'woocommerce_refund_deleted', $refund_id, $order_id );

			update_post_meta( $order_id, 'sos_refund_notice', $e->getMessage() );
			Utils::unset_returning_to_wc_refund();
			wp_redirect( admin_url( 'edit.php?post_type=shop_order' ) );
		}

		try {
			if ( 'shop_order_refund' !== get_post_type( $refund_id ) ) {
				throw new \Exception( "Refund doesn't exist or deleted" );
			}
		} catch ( \Exception $e ) {
			update_post_meta( $order_id, 'sos_refund_notice', $e->getMessage() );
			Utils::unset_returning_to_wc_refund();
			wp_redirect( get_edit_post_link( $order_id, '' ) );
		}

		try {
			if ( ! isset( $query_str['request-id'] ) || empty( $query_str['request-id'] ) ) {
				throw new \Exception( 'Something went wrong, please try again', 1 );
			}

			if ( isset( $query_str['error'] ) && ! empty( $query_str['error'] ) ) {
				if ( isset( $query_str['message'] ) && ! empty( $query_str['message'] ) ) {
					throw new \Exception( 'Refund cancelled: ' . $query_str['message'], 1 );
				}
				throw new \Exception( 'Something went wrong, please try again', 1 );
			}

			if ( ! isset( $query_str['tokenId'] ) || empty( $query_str['tokenId'] ) ) {
				throw new \Exception( 'Something went wrong, please try again', 1 );
			}

			header( 'HTTP/1.1 200 OK' );
			// Prepare arguments for updating the Refund Order on ShoppingOS Service side.
			$params = array_merge(
				$query_str,
				[
					'app_id'      => $this->app_id,
					'app_secret'  => $this->app_secret,
					'environment' => $this->testmode ? 'sandbox' : 'production',
				]
			);
			$response = $this->send_success_callback( $params, true );

			if ( ! is_wp_error( $response ) ) {
				$response_body = json_decode( $response['body'], true );
				if ( isset( $response_body['code'] ) ) {
					if ( $response_body['code'] === 'success' ) {
						update_post_meta( $order_id, 'sos_refund_notice', 'Refund is successful.' );
						update_post_meta( $order_id, 'sos_refund_status', 'processing' );
						Utils::add_order_note_successfull_refund( $order_id, $refund_id );
					} elseif ( isset( $response_body['code'] ) && $response_body['code'] === 'error' ) {
						update_post_meta( $order_id, 'sos_refund_notice', $response_body['message'] );
					}
				} else {
					/**
					 * @todo throw exception here or raise an error
					 */
				}
			} else {
				update_post_meta( $order_id, 'sos_refund_notice', 'Response not received' );
			}

			Utils::unset_returning_to_wc_refund();
			wp_redirect( get_edit_post_link( $order_id, '' ) );

		} catch ( \Exception $e ) {
			$params = array_merge(
				$query_str,
				[
					'app_id'      => $this->app_id,
					'app_secret'  => $this->app_secret,
					'environment' => $this->testmode ? 'sandbox' : 'production',
				]
			);
			$response = $this->send_fail_callback( $params, true );

			if ( ! is_wp_error( $response ) ) {
				$response_body = json_decode( $response['body'], true );

				update_post_meta( $order_id, 'sos_refund_notice', 'Refund failed or cancelled' );

				if ( isset( $response_body['error'] ) ) {
					update_post_meta( $order_id, 'sos_refund_response_notice', 'Response not received: ' . $response_body['error'] );
				} elseif ( isset( $response_body['code'] ) && $response_body['code'] === 'received' ) {
					update_post_meta( $order_id, 'sos_refund_response_notice', 'Response: ' . $response_body['error'] );
				}
			} else {
				update_post_meta( $order_id, 'sos_refund_response_notice', 'Response not received' );
			}

			$refund = \wc_get_order( $refund_id );
			if ( $refund ) {
				$refund->delete( true );
			}

			Utils::unset_returning_to_wc_refund();
			wp_redirect( get_edit_post_link( $order_id, '' ) );
		}
	}

	/**
	 * Handle and output corresponding notices on the end of refund
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function refund_notice_handler() {
		if ( ! Utils::is_edit_shop_order_page() ) {
			return;
		}

		if ( ! isset( $_GET['post'] ) || empty( $_GET['post'] ) ) {
			return;
		}

		$post_id = sanitize_text_field( $_GET['post'] );

		/**
		 * @todo consider using sessions to hold notices
		 */
		$refund_notice = get_post_meta( $post_id, 'sos_refund_notice', true );
		$refund_response_notice = get_post_meta( $post_id, 'sos_refund_response_notice', true );

		if ( ! empty( $refund_notice ) ) {
			if ( $refund_notice === 'Refund is successful.' ) {
				$notice_class = 'notice-success';
			} else {
				$notice_class = 'error';
			}

			?>

			<div class="notice <?php echo $notice_class; ?> is-dismissible">
				<p><?php echo $refund_notice; ?></p>
			</div>

			<?php
			delete_post_meta( $post_id, 'sos_refund_notice', $refund_notice );
		}

		if ( ! empty( $refund_response_notice ) ) {
			if ( $refund_notice === 'Refund is successful.' ) {
				$notice_class = 'notice-success';
			} else {
				$notice_class = 'error';
			}

			?>

			<div class="notice <?php echo $notice_class; ?> is-dismissible">
				<p><?php echo $refund_response_notice; ?></p>
			</div>

			<?php
			delete_post_meta( $post_id, 'sos_refund_response_notice', $refund_response_notice );
		}
	}
}
