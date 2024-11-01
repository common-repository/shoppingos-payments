<?php
namespace ShoppingOS;

use ShoppingOS\Config\Plugin;
use ShoppingOS\Traits\Singleton;

/**
 * ScriptStyle class
 *
 * Enqueue script and styles required for the plugin
 *
 * @since   2.0.0
 * @package ShoppingOS
 */
class ScriptStyle {
	use Singleton;

	/**
	 * Class constructor
	 *
	 * @since 2.0.0
	 * @return void
	 */
	protected function __construct() {
		$this->plugin = Plugin::init();

		add_action( 'woocommerce_after_checkout_form', [ $this, 'checkout_scripts' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'shoppingos_scripts' ], 0 );
		add_action( 'admin_footer', [ $this, 'load_admin_style' ] );
		add_action( 'admin_footer', [ $this, 'load_admin_script' ] );
	}

	/**
	 * Enqueues scripts required for checkout page
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function checkout_scripts() {
		wp_enqueue_script(
			'select2_script',
			$this->plugin->assetsUrl() . 'js/select2.min.js',
			[ 'jquery' ],
			$this->plugin->version(),
			false
		);

		wp_enqueue_script(
			'checkout_script',
			$this->plugin->assetsUrl() . 'js/plugin-utils.js',
			[ 'jquery' ],
			$this->plugin->version(),
			false
		);
	}

	/**
	 * Enqueues styles for the plugin
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function shoppingos_scripts() {
		wp_enqueue_style(
			'select2_style',
			$this->plugin->assetsUrl() . 'css/select2.min.css',
			[],
			$this->plugin->version(),
			false
		);

		/**
		 * @todo consider using more verbose name for the function
		 * It's not clear if these style are common or public or admin.
		 */
		wp_enqueue_style(
			'shoppingos_style',
			$this->plugin->assetsUrl() . 'css/style.css',
			[],
			$this->plugin->version(),
			false
		);
	}

	/**
	 * Enqueues admin styles
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function load_admin_style() {
		wp_enqueue_style(
			'shoppingos_payments_admin',
			$this->plugin->assetsUrl() . 'css/admin-style.css',
			[],
			$this->plugin->version(),
			false
		);
	}

	/**
	 * Enqueues admin scripts
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function load_admin_script() {
		wp_enqueue_script(
			'shoppingos_payments_admin',
			$this->plugin->assetsUrl() . 'js/admin.js',
			[],
			$this->plugin->version(),
			false
		);
	}
}
