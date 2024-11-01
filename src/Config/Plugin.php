<?php
namespace ShoppingOS\Config;

use ShoppingOS\Traits\Singleton;

/**
 * Plugin data which are used through the plugin, most of them are defined
 * by the root file meta data. The data is being inserted in each class
 * that extends the Base abstract class
 *
 * @see Base
 * @package ShoppingOS\Config
 * @since 2.0.0
 */
final class Plugin {
	/**
	 * Singleton trait
	 */
	use Singleton;

	/**
	 * Get the plugin meta data from the root file and include own data
	 *
	 * @return array
	 * @since 2.0.0
	 */
	public function data(): array {
		$plugin_data = apply_filters( 'sos_plugin_data', [
			'plugin_path'            => untrailingslashit(
				plugin_dir_path( SOS_PLUGIN_FILE )  // phpcs:disable ImportDetection.Imports.RequireImports.Symbol -- this constant is global
			),
			'assets_url'             => plugins_url( 'assets/', SOS_PLUGIN_FILE ),
			/**
			 * Add extra data here
			 */
		] );
		return array_merge(
			apply_filters( 'sos_plugin_metadata',
				get_file_data( SOS_PLUGIN_FILE, // phpcs:disable ImportDetection.Imports.RequireImports.Symbol -- this constant is global
					[
						'name'         => 'Plugin Name',
						'uri'          => 'Plugin URI',
						'description'  => 'Description',
						'version'      => 'Version',
						'author'       => 'Author',
						'author-uri'   => 'Author URI',
						'text-domain'  => 'Text Domain',
						'domain-path'  => 'Domain Path',
						'required-php' => 'Requires PHP',
						'required-wp'  => 'Requires at least',
						'required-wc'  => 'WC Requires at least',
						'namespace'    => 'Namespace',
					], 'plugin'
				)
			), $plugin_data
		);
	}

	/**
	 * Get the plugin path
	 *
	 * @return string
	 * @since 2.0.0
	 */
	public function pluginPath(): string {
		return $this->data()['plugin_path'];
	}

	/**
	 * Get the plugin assets URL
	 *
	 * @return string
	 * @since 2.0.0
	 */
	public function assetsUrl(): string {
		return $this->data()['assets_url'];
	}

	/**
	 * Get the plugin version number
	 *
	 * @return string
	 * @since 2.0.0
	 */
	public function version(): string {
		return $this->data()['version'];
	}

	/**
	 * Get the required minimum PHP version
	 *
	 * @return string
	 * @since 2.0.0
	 */
	public function requiredPhp(): string {
		return $this->data()['required-php'];
	}

	/**
	 * Get the required minimum WP version
	 *
	 * @return string
	 * @since 2.0.0
	 */
	public function requiredWp(): string {
		return $this->data()['required-wp'];
	}

	/**
	 * Get the required minimum WC version
	 *
	 * @return string
	 * @since 2.0.0
	 */
	public function requiredWc(): string {
		return $this->data()['required-wc'];
	}

	/**
	 * Get the plugin name
	 *
	 * @return string
	 * @since 2.0.0
	 */
	public function name(): string {
		return $this->data()['name'];
	}

	/**
	 * Get the plugin url
	 *
	 * @return string
	 * @since 2.0.0
	 */
	public function uri(): string {
		return $this->data()['uri'];
	}

	/**
	 * Get the plugin description
	 *
	 * @return string
	 * @since 2.0.0
	 */
	public function description(): string {
		return $this->data()['description'];
	}

	/**
	 * Get the plugin author
	 *
	 * @return string
	 * @since 2.0.0
	 */
	public function author(): string {
		return $this->data()['author'];
	}

	/**
	 * Get the plugin author uri
	 *
	 * @return string
	 * @since 2.0.0
	 */
	public function authorUri(): string {
		return $this->data()['author-uri'];
	}

	/**
	 * Get the plugin text domain
	 *
	 * @return string
	 * @since 2.0.0
	 */
	public function textDomain(): string {
		return $this->data()['text-domain'];
	}

	/**
	 * Get the plugin domain path
	 *
	 * @return string
	 * @since 2.0.0
	 */
	public function domainPath(): string {
		return $this->data()['domain-path'];
	}

	/**
	 * Get the plugin namespace
	 *
	 * @return string
	 * @since 2.0.0
	 */
	public function namespace(): string {
		return $this->data()['namespace'];
	}
}
