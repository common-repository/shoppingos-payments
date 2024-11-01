<?php
/*
 * Plugin Name: ShoppingOS Payments
 * Plugin URI: https://shoppingos.com
 * Description: ShoppingOS Payments enables account-2-account complete payment processing solution.
 * Version: 2.0.3
 * Requires at least: 5.8
 * Requires PHP: 7.2
 * WC requires at least: 5.9
 * Author: ShoppingOS
 * License:  GPL v3
 * Namespace: ShoppingOS
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

/*
 * Abort if file is called directly
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

const SOS_PLUGIN_FILE = __FILE__;

/**
 * Load PSR4 autoloader
 *
 * @since 2.0.0
 */
require plugin_dir_path( SOS_PLUGIN_FILE ) . 'vendor/autoload.php';

/**
 * Bootstrap the plugin
 *
 * @since 2.0.0
 */
add_action(
	'plugins_loaded',
	static function () {
		/**
		 * Check if the setup is meeting requirements
		 */
		if ( ! ( new ShoppingOS\Config\Requirements() )->satisfied() ) {
			unset( $_GET['activate'] );
			add_action( 'admin_init',
				static function () {
					deactivate_plugins( plugin_basename( SOS_PLUGIN_FILE ) ); // phpcs:disable ImportDetection.Imports.RequireImports.Symbol -- this constant is global
				}
			);
			return;
		}

		ShoppingOS\Checkout::init();
		ShoppingOS\ScriptStyle::init();
		new ShoppingOS\Refund();

		/*
		 * Register the class as a WooCommerce payment gateway
		 */
		add_filter( 'woocommerce_payment_gateways', 'shoppingos_gateway_class' );

		function shoppingos_gateway_class( $gateways ) {
			$gateways[] = 'ShoppingOS\\Gateway';
			return $gateways;
		}

		// add a 'Configure' link to the plugin action links
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'shoppingos_plugin_links' );

		/**
		 * Adds plugin action links
		 *
		 * @since 1.0.0
		 * @param array $links Plugin action link before filtering.
		 * @return array Filtered links.
		 */
		function shoppingos_plugin_links( $links ) {
			$setting_link = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=shopping_os' );

			$plugin_links = [
				'<a href="' . $setting_link . '">' . __( 'Configure' ) . '</a>',
				'<a href="https://service.shoppingos.com">' . __( 'Dashboard' ) . '</a>',
			];

			return array_merge( $plugin_links, $links );
		}
	}
);
