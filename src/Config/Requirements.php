<?php
namespace ShoppingOS\Config;

use ShoppingOS\Abstracts\Base;

/**
 * Check if any requirements are needed to run this plugin. We use the
 * "Requirements" package from "MicroPackage" to check if any PHP Extensions,
 * plugins, themes or PHP/WP version are required.
 *
 * @package ShoppingOS\Config
 * @since 2.0.0
 */
final class Requirements extends Base {

	/**
	 * Specifications for the requirements
	 *
	 * @return array : used to specify the requirements
	 * @since 2.0.0
	 */
	public function specifications(): array {
		return apply_filters( 'sos_plugin_requirements', [
			'php'            => $this->plugin->requiredPhp(),
			'php_extensions' => [
				/**
				 * 'mbstring'
				 */
			],
			'wp'             => $this->plugin->requiredWp(),
			'plugins'        => [
				[
					'file'    => 'woocommerce/woocommerce.php',
					'name'    => 'Woocommerce',
					'version' => $this->plugin->requiredWc(),
				],
			],
		] );
	}

	/**
	 * Plugin requirements checker
	 *
	 * @since 2.0.0
	 */
	public function satisfied() {
		// We use "Requirements" if the package is required and installed by composer.json
		if ( class_exists( '\Micropackage\Requirements\Requirements' ) ) {
			$this->requirements = new \Micropackage\Requirements\Requirements(
				$this->plugin->name(),
				$this->specifications()
			);
			if ( ! $this->requirements->satisfied() ) {
				// Print notice
				$this->requirements->print_notice();
				return false;
			} else {
				return true;
			}
		} else {
			// Else we do a version check based on version_compare
			return $this->versionCompare();
		}
	}

	/**
	 * Compares PHP & WP versions and kills plugin if it's not compatible
	 *
	 * @since 2.0.0
	 */
	public function versionCompare() {
		foreach (
			[
				// PHP version check
				[
					'current' => phpversion(),
					'compare' => $this->plugin->requiredPhp(),
					'title'   => __( 'Invalid PHP version' ),
					'message' => sprintf( /* translators: %1$1s: required php version, %2$2s: current php version */
						__( 'You must be using PHP %1$1s or greater. You are currently using PHP %2$2s.' ),
						$this->plugin->requiredPhp(),
						phpversion()
					),
				],
				// WP version check
				[
					'current' => get_bloginfo( 'version' ),
					'compare' => $this->plugin->requiredWp(),
					'title'   => __( 'Invalid WordPress version' ),
					'message' => sprintf( /* translators: %1$1s: required wordpress version, %2$2s: current wordpress version */
						__( 'You must be using WordPress %1$1s or greater. You are currently using WordPress %2$2s.' ),
						$this->plugin->requiredWp(),
						get_bloginfo( 'version' )
					),
				],
			] as $compat_check ) {
			if ( version_compare(
				$compat_check['compare'],
				$compat_check['current'],
				'>='
			) ) {
				return false;
			}
		}
		return true;
	}
}
