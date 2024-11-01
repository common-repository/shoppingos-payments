<?php
namespace ShoppingOS;

use ShoppingOS\Config\Plugin;
use ShoppingOS\Traits\SendSuccessFail;
use ShoppingOS\Utils;
use ShoppingOS\EndpointsV1;

/**
 * Gateway class
 *
 * ShoppingOS Payment represents a SaaS platform that
 *
 * @since   1.0.0
 * @package WooCommerce\Classes
 */
class Gateway extends \WC_Payment_Gateway {
	use SendSuccessFail;

	/**
	 * Class constructor
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		$this->plugin             = Plugin::init();
		$this->endpoints          = EndpointsV1::init();
		$this->id                 = 'shopping_os';
		$this->icon               = plugins_url( '../assets/images/shoppingos-wide-logo.svg', __FILE__ );
		$this->method_title       = 'ShoppingOS - Pay with Bank';
		$this->method_description = 'Have your customers pay directly from their bank accounts.';
		$this->supports           = [ 'products', 'refunds' ];

		// Init payment method settings
		$this->init_form_fields();
		// Load the settings.
		$this->init_settings();

		$this->title       = $this->get_option( 'title' );
		$this->description = '🔒 Pay securely with your bank app.';
		$this->enabled     = $this->get_option( 'enabled' );

		// Define the environment of the plugin.
		$this->testmode = 'yes' === $this->get_option( 'testmode' );

		// Flag for default payment gateway
		$this->default = 'yes' === $this->get_option( 'default' );

		// Flag for default payment gateway
		$this->display_link = 'yes' === $this->get_option( 'default' );

		/*
		 * API credentials for ShoppingOS Service API.
		 *
		 * The credentials are obtained by the merchant after performing registration on the link below.
		 * @link https://service.shoppingos.com
		 */
		$this->app_id     = $this->get_option( 'app_id' );
		$this->app_secret = $this->get_option( 'app_secret' );

		// This action hook saves the settings
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );

		// Register payment callback webhook
		add_action( 'woocommerce_api_shoppingos-payment', [ $this, 'shoppingos_webhook' ] );

		if ( $this->get_option( 'default' ) === 'yes' ) {
			if ( isset( \WC()->session ) ) {
				\WC()->session->set( 'chosen_payment_method', 'shopping_os' );
			}
		}

		if ( $this->get_option( 'enabled' ) === 'yes' ) {
			// Register statistics dashboard widget
			add_action( 'wp_dashboard_setup', [ $this, 'shoppingos_dashoard_widget' ] );
		}

		if ( is_admin() ) {
			// Add filter by Payment Method to Orders list page
			add_action( 'restrict_manage_posts', [ $this, 'filter_orders_by_payment_method' ], 20 );
			add_filter( 'request', [ $this, 'filter_orders_by_payment_method_query' ] );
		}
	}

	/**
	 * Add sidebar to the settings page.
	 *
	 * @since 1.0.2
	 * @return void
	 */
	public function admin_options() {
		ob_start();
		parent::admin_options();
		$parent_options = ob_get_contents();
		ob_end_clean();

		$this->settings_sidebar( $parent_options );
	}

	/**
	 * Adds sidebar to setting page settings.
	 *
	 * @since 1.0.2
	 * @param string $parent_options The parent options.
	 */
	public static function settings_sidebar( $parent_options ) {
		?>
		<img class="shoppingos-settings-logo" src="<?php echo plugins_url( '../assets/images/shoppingos-wide-logo.png', __FILE__ ); ?>" width="200"/>

		<div class="shoppingos-content">
			<div class="shoppingos-main">
				<?php echo $parent_options; ?>
				</br>
				<p class="shoppingos-sidebar-main-text">
					For questions regarding technical functionality or plugin configuration you are welcome to <a href="https://www.shoppingos.com/inner-pages/contact" target="_blank">contact us</a>.
				</p>
			</div>

			<div class="shoppingos-sidebar">
				<div class="shoppingos-sidebar-section">
					<div class="shoppingos-sidebar-content">
						<h1 class="shoppingos-sidebar-title">Get started</h1>
						<p class="shoppingos-sidebar-main-text">
							Please sign in to <a href="https://service.shoppingos.com" target="_blank">ShoppingOS Dashboard</a>
							in order to obtain API Credentials
						</p>

						<h1 class="shoppingos-sidebar-title">Testing</h1>
						<p class="shoppingos-sidebar-main-text">
							Once the <strong>Test mode</strong> is enabled, you can test the Account-2-Account payments using the Sandbox environment of Banks.
						</p>

						<p class="shoppingos-sidebar-main-text">
							Here are some credentials you can use for testing purpose:
						</p>

						<hr class="line-break">
						<strong>Lloyds Bank (United Kingdom)</strong>
						<span><strong>Username: </strong> llr001</span>
						<span><strong>Password: </strong> Password123</span>

						<hr class="line-break">

						<strong>Ozone Modelo Test Bank (United Kingdom)</strong>
						<span><strong>Username: </strong> mits</span>
						<span><strong>Password: </strong> mits</span>
						<hr class="line-break">

						<h1 class="shoppingos-sidebar-title">Go Live</h1>
						<p class="shoppingos-sidebar-main-text">
							Once you have tested the Account-2-Account payments and decided it's time to save money on fees,
							please <a href="https://www.shoppingos.com/inner-pages/contact" target="_blank">contact us</a>
							in order to discuss our pricing.
						</p>
					</div>
				</div>
			</div>
		</div>
		<div class="save-separator"></div>
		<?php
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields = [
			'enabled' => [
				'title'       => 'Enable/Disable',
				'label'       => 'Enable ShoppingOS Payment',
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			],
			'default' => [
				'title'       => 'Default',
				'label'       => 'Set as default payment method',
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			],
			'display_link' => [
				'title'       => 'Display "Learn more" link?',
				'label'       => 'Adds a "Learn more" link at the end of payment method description.',
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			],
			'title' => [
				'title'       => 'Title',
				'type'        => 'text',
				'description' => 'This controls the title which the user sees during checkout.',
				'default'     => 'Pay with Bank',
			],
			'testmode' => [
				'title'       => 'Test mode',
				'label'       => 'Enable Test Mode',
				'type'        => 'checkbox',
				'description' => 'Test the payment method using sandbox environemnts of banks.',
				'default'     => 'yes',
			],
			'app_id' => [
				'title'       => 'App ID',
				'type'        => 'text',
			],
			'app_secret' => [
				'title'       => 'App Secret',
				'type'        => 'password',
			],
		];
	}

	/**
	 * Payment form on checkout page
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function payment_fields() {

		if ( $this->display_link ) {
			$this->description .= '</br><a href="https://www.shoppingos.com/how-it-works" target="_blank" class="sos_link">Learn more.</a>';
			$this->description  = trim( $this->description );
		}

		echo '<div id="sos-payment-data">';
		echo wpautop( wp_kses_post( $this->description ) );

		$this->print_progress_bar();
		$this->print_bank_dropdown();

		echo '<div class="sos-icons-label">Or Select from the Below Banks:</div>';

		if ( $this->testmode ) {
			$this->print_sandbox_bank_icons();
		} else {
			$this->print_production_bank_icons();
		}

		echo '</div>';
	}

	/**
	 * Prints the progress bar to represent the user journey.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_progress_bar() {
		?>
		<div class="sos-progress-wrapper">
			<div class="sos-progress">
				<div class="bar"></div>
				<div class="sos-point sos-point--complete">
					<label class="down">Select bank</label>
					<div class="sos-bullet"></div>
				</div>
				<div class="sos-point sos-point--complete">
					<div class="sos-bullet"></div>
					<label class="up">Approve</label>
				</div>
				<div class="sos-point sos-point--complete">
					<div class="sos-bullet"></div>
					<label class="down">Redirect to bank</label>
				</div>
				<div class="sos-point sos-point--complete">
					<div class="sos-bullet"></div>
					<label class="up">Confirm</label>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Prints the production bank list for quick bank selection.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_bank_dropdown() {
		?>
		<div class="sos-selector-wrapper">
			<label for="sos-selector-input-label">
				Bank Search by Name:
				<select class="sos-selector-input" name="icon">
					<option value="" disabled selected></option>
				</select>
			</label>
		</div>
		<?php
	}

	/**
	 * Prints the production bank icons for quick bank selection.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_production_bank_icons() {
		?>
		<div class="icons-list">
			<input id="bank-icon" title="Proceed with Barclays Bank" onclick="setHidden('ob-barclays');" type="image" name="submit" src="<?php echo plugins_url( '../assets/images/banks/barclays.svg', __FILE__ ); ?>" alt="Submit" />
			<input id="bank-icon" title="Proceed with Monzo Bank" onclick="setHidden('ob-monzo');" type="image" name="submit" src="<?php echo plugins_url( '../assets/images/banks/monzo.svg', __FILE__ ); ?>" alt="Submit" />
			<input id="bank-icon" title="Proceed with HSBC" onclick="setHidden('ob-hsbc');" type="image" name="submit" src="<?php echo plugins_url( '../assets/images/banks/hsbc.svg', __FILE__ ); ?>" alt="Submit" />
			<input id="bank-icon" title="Proceed with Lloyds Bank" onclick="setHidden('ob-lloyds');" type="image" name="submit" src="<?php echo plugins_url( '../assets/images/banks/lloyds.svg', __FILE__ ); ?>" alt="Submit" />
			<input id="bank-icon" title="Proceed with Revolut" onclick="setHidden('ob-revolut');" type="image" name="submit" src="<?php echo plugins_url( '../assets/images/banks/revolut.svg', __FILE__ ); ?>" alt="Submit" />
		</div>
		<?php
	}

	/**
	 * Prints the sandbox bank icons for quick bank selection.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function print_sandbox_bank_icons() {
		?>
		<div class="icons-list">
			<input id="bank-icon" title="Proceed with Lloyds Bank" onclick="setHidden('ob-lloyds');" type="image" name="submit" src="<?php echo plugins_url( '../assets/images/banks/lloyds.svg', __FILE__ ); ?>" alt="Submit" />
			<input id="bank-icon" title="Proceed with Ozone Modelo Bank" onclick="setHidden('ob-modelo');" type="image" name="submit" src="<?php echo plugins_url( '../assets/images/banks/modelo.png', __FILE__ ); ?>" alt="Submit" />
			<span class="footer-text">Testing mode enabled, please use the bank icons. </span>
		</div>
		<?php
	}

	/**
	 * Process the payment
	 *
	 * @since 1.0.0
	 * @param int $order_id Reference.
	 * @return void
	 */
	public function process_payment( $order_id ) {
		$order = \wc_get_order( $order_id );
		$environment = $this->testmode ? 'sandbox' : 'production';

		$request_args = [
			'headers' => [
				'App-Id'     => $this->app_id,
				'App-Secret' => $this->app_secret,
			],
			'body' => [
				'payment_details' => [
					'environment'  => $environment,
					'order_id'     => $order_id,
					'total'        => $order->get_total(),
					'currency'     => $order->get_currency(),
					'callback_url' => get_site_url() . '/?wc-api=shoppingos-payment&order_id=' . $order_id,
					'user_agent'   => $order->get_customer_user_agent(),
					'psu_checksum' => hash( 'crc32b', $order->get_billing_email() ),
					'bank_code'    => get_post_meta( $order->get_id(), 'sos_selected_bank', true ),
					'version'      => $this->plugin->version(),
				],
			],
		];

		/**
		 * Send Payment Order request to ShoppingOS Service
		 */
		$response = wp_remote_post( $this->endpoints->endpoint( 'token' ), $request_args );

		if ( ! is_wp_error( $response ) ) {
			$response_body = json_decode( $response['body'], true );

			if ( $response_body['code'] === 'received' ) {
				// Redirect the customer for bank selection
				return [
					'result'   => 'success',
					'redirect' => $response_body['redirect_url'],
				];
			} elseif ( $response_body['code'] === 'error' ) {
				\wc_add_notice( $response_body['message'], 'error' );
				return;
			} else {
				\wc_add_notice( 'Please try again.', 'error' );
				return;
			}
		} else {
			\wc_add_notice( 'Connection error.', 'error' );
			return;
		}
	}

	/**
	 * Receives the payment result
	 *
	 * After authorizing and confirming the payment on bank side,
	 * the customer is redirected back to merchant website with the result of the payment.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function shoppingos_webhook() {
		parse_str( $_SERVER['QUERY_STRING'], $query_str );

		$order_id    = sanitize_text_field( $query_str['order_id'] );
		$order       = \wc_get_order( $order_id );

		update_post_meta( $order_id, 'sos_token_id', sanitize_text_field( $query_str['tokenId'] ) );

		/**
		 * Check if the payment order was completed successfully or not.
		 */
		if ( isset( $query_str['message'] ) && ! empty( $query_str['message'] ) ) {

			if ( isset( $query_str['message'] ) && ! empty( $query_str['message'] ) ) {
				$customer_notice = sanitize_text_field( $query_str['message'] );
			} else {
				$customer_notice = 'Please try again';
			}

			\wc_add_notice( $customer_notice, 'error' );

			$params = array_merge(
				$query_str,
				[
					'app_id'      => $this->app_id,
					'app_secret'  => $this->app_secret,
					'environment' => $this->testmode ? 'sandbox' : 'production',
				]
			);
			$response = $this->send_fail_callback( $params, false );

			if ( ! is_wp_error( $response ) ) {
				$response_body         = json_decode( $response['body'], true );
				$additional_error_info = sanitize_text_field( $response_body['message'] );
			} else {
				$additional_error_info = "Couldn't receive fail callback response";
			}

			$order_note     = [];
			$order_note[]   = '<b>Payment Attempt</b>';
			$order_note[]   = "Customer Notice: $customer_notice";
			$order_note[]   = "Additionally: $additional_error_info";
			$order_note_str = implode( PHP_EOL, $order_note );

			$order->add_order_note( $order_note_str );
			$order->update_status( 'failed' );

			// Redirect the customer to checkout page with the right notice.
			wp_redirect( \wc_get_checkout_url() );
			exit();
		}

		header( 'HTTP/1.1 200 OK' );

		$params = array_merge(
			$query_str,
			[
				'app_id'      => $this->app_id,
				'app_secret'  => $this->app_secret,
				'environment' => $this->testmode ? 'sandbox' : 'production',
			]
		);
		$response = $this->send_success_callback( $params, false );

		if ( ! is_wp_error( $response ) ) {
			$response_body = json_decode( $response['body'], true );
			if ( $response_body['code'] === 'error' ) {
				$customer_notice = sanitize_text_field( $response_body['message'] );
				$payment_status  = sanitize_text_field( $response_body['status'] );

				$order_note     = [];
				$order_note[]   = '<b>Payment Attempt</b>';
				$order_note[]   = "Customer Notice: $customer_notice";
				$order_note[]   = "Received Status: $payment_status";
				$order_note_str = implode( PHP_EOL, $order_note );

				$order->add_order_note( $order_note_str );
				$order->update_status( 'failed' );

				\wc_add_notice( $customer_notice, 'error' );
				wp_redirect( \wc_get_checkout_url() );
				exit();

			} elseif ( $response_body['code'] === 'success' ) {
				$order->payment_complete();
				$order->reduce_order_stock();
			} elseif ( $response_body['code'] !== 'success' ) {
				\wc_add_notice( 'Please try again.', 'error' );
			}
		} else {
			\wc_add_notice( 'Response not received', 'error' );
		}

		// After completing the payment, customer is redirected to the order page to see the result.
		wp_redirect( $this->get_return_url( $order ) );
		exit();
	}

	/**
	 * Process the refund
	 *
	 * @since 2.0.0
	 * @param int $order_id Reference.
	 * @param int $amount Amount.
	 * @param string $reason The reason of refund
	 * @return void
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = new \WC_Order( $order_id );
		$refunds = $order->get_refunds();
		$current_refund = reset( $refunds );

		Utils::set_redirecting_to_bank_refund( $current_refund->get_id(), $order_id );
		update_post_meta( $order_id, 'sos_refund_current', $current_refund->get_id() );
		return true;
	}

	public function shoppingos_dashoard_widget() {
		wp_add_dashboard_widget( 'shoppingos_payments_widget', 'Payments via ShoppingOS', [ $this, 'shoppingos_dashoard_widget_statistics' ] );
	}

	/**
	 * Renders the dashboard wigdet with payment statistics
	 *
	 * After authorizing and confirming the payment on bank side,
	 * the customer is redirected back to merchant website with the result of the payment.
	 *
	 * @since 1.0.2
	 * @return void
	 */
	public function shoppingos_dashoard_widget_statistics() {
		$total_args = [
			'payment_method' => 'shopping_os',
			'orderby'        => 'date',
			'order'          => 'DESC',
			'limit'          => -1,
		];
		$total_orders = \wc_get_orders( $total_args );

		$finished_orders = array_filter( $total_orders, function( $order ): bool {
			return in_array( $order->get_status(), [ 'processing', 'completed' ], true );
		} );

		$unfinished_orders = array_filter( $total_orders, function( $order ): bool {
			return in_array( $order->get_status(), [ 'pending', 'cancelled', 'on-hold' ], true );
		} );

		$total_processed = 0;
		$min_saved = 0;
		$max_saved = 0;
		$sos_fee = 0;
		foreach ( $finished_orders as $order ) {
			$min_saved += 0.2 + $order->get_total() * 0.014;
			$max_saved += 0.39 + $order->get_total() * 0.0349;
			$total_processed += $order->get_total();
			$sos_fee += 0.5;
			if ( get_post_meta( $order->get_id(), 'sos_refund_status', true ) === 'processing' ) {
				$sos_fee += 0.5;
			}
		}

		$statistics_data = [
			'total_orders'      => $total_orders,
			'finished_orders'   => $finished_orders,
			'unfinished_orders' => $unfinished_orders,
			'total_processed'   => $total_processed,
			'min_saved'         => $min_saved,
			'max_saved'         => $max_saved,
			'sos_fee'           => $sos_fee,
		];

		$this->display_orders( $statistics_data );
	}

	/**
	 * Display orders for dashboard wigdet
	 *
	 * @since 1.0.2
	 * @return void
	 */
	public function display_orders( $statistics_data ) {
		?>
		<div class="sos-widget-content">
			<div class="sos-widget-header">
				<div class="sos-widget-left-column">
					<span class="sos-widget-text">
						<b>Total payments: </b> <?php echo count( $statistics_data['total_orders'] ); ?>
					</span>
					<span class="sos-widget-text">
						<b>Completed payments: </b> <?php echo count( $statistics_data['finished_orders'] ); ?>
					</span>
					<span class="sos-widget-text">
						<b>Incomplete payments: </b> <?php echo count( $statistics_data['unfinished_orders'] ); ?>
					</span>
				</div>
				<div class="sos-widget-right-column">
					<span class="sos-widget-text">
						<b>Total amount: </b> <?php echo \get_woocommerce_currency_symbol() . $statistics_data['total_processed'] . ' ' . \get_woocommerce_currency(); ?>
					</span>
					<span class="sos-widget-text">
						<b>Savings: </b> <?php echo \get_woocommerce_currency_symbol() . number_format( (float) $statistics_data['min_saved'], 2, '.', '' ) . ' - ' . \get_woocommerce_currency_symbol() . number_format( (float) $statistics_data['max_saved'], 2, '.', '' ) . ' ' . \get_woocommerce_currency(); ?>
					</span>
					<span class="sos-widget-text">
						<b>ShoppingOS fee: </b> <?php echo \get_woocommerce_currency_symbol() . $statistics_data['sos_fee'] . ' ' . \get_woocommerce_currency(); ?>
					</span>
				</div>
			</div>
			<table class="wp-list-table widefat fixed striped table-view-list posts sos-statistics-table">
				<tr class="sos-tr">
					<th class="sos-th">Order</th>
					<th class="sos-th">Total</th>
					<th class="sos-th">Status</th>
				</tr>
			<?php

			$recent_orders = array_slice( $statistics_data['total_orders'], -10, 10, true );

			foreach ( $recent_orders as $order ) {
				$order_id = $order->get_id();
				?>
				<tr class="sos-tr">
					<td class="sos-td">
						<a href="<?php echo get_edit_post_link( $order_id, '' ); ?>">
							<?php echo '#' . $order_id . ' ' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(); ?>
						</a>
					</td>
					<td class="sos-td"><?php echo \get_woocommerce_currency_symbol() . $order->get_total() . ' ' . $order->get_currency(); ?></td>
					<td class="sos-td"><?php echo esc_html( \wc_get_order_status_name( $order->get_status() ) ); ?></td>
				</tr>
				<?php
			}
			?>
			</table>

			<?php
				$sos_order_link = add_query_arg( [
					'post_status' => 'all',
					'post_type' => 'shop_order',
					'_shop_order_payment_method' => 'shopping_os',
				], get_site_url() . '/wp-admin/edit.php' );

				echo "<a class='sos-view-all' href='" . $sos_order_link . "'>All payments via ShoppingOS</a>";
			?>
		</div>
		<?php
	}

	/**
	 * Add bulk filter for orders by payment method
	 *
	 * @since 1.0.2
	 */
	public function filter_orders_by_payment_method() {
		global $typenow;

		if ( 'shop_order' === $typenow ) {

			// get all payment methods, even inactive ones
			$gateways = \WC()->payment_gateways->get_available_payment_gateways();

			?>
			<select name="_shop_order_payment_method" id="dropdown_shop_order_payment_method">
				<option value="">
					<?php esc_html_e( 'All Payment Methods', 'wc-filter-orders-by-payment' ); ?>
				</option>

				<?php foreach ( $gateways as $id => $gateway ) : ?>
				<option value="<?php echo esc_attr( $id ); ?>" <?php echo esc_attr( isset( $_GET['_shop_order_payment_method'] ) ? selected( $id, $_GET['_shop_order_payment_method'], false ) : '' ); ?>>
					<?php echo esc_html( $gateway->get_method_title() ); ?>
				</option>
				<?php endforeach; ?>
			</select>
			<?php
		}
	}

	/**
	 * Process bulk filter order payment method
	 *
	 * @since 1.0.2
	 *
	 * @param array $vars query vars without filtering
	 * @return array $vars query vars with (maybe) filtering
	 */
	public function filter_orders_by_payment_method_query( $vars ): array {
		global $typenow;

		if ( 'shop_order' === $typenow && isset( $_GET['_shop_order_payment_method'] ) && ! empty( $_GET['_shop_order_payment_method'] ) ) {

			$vars['meta_key']   = '_payment_method';
			$vars['meta_value'] = \wc_clean( $_GET['_shop_order_payment_method'] );
		}

		return $vars;
	}
}
