<?php
namespace ShoppingOS\Abstracts;

use ShoppingOS\Config\Plugin;

/**
 * The Base class which can be extended by other classes to load in default methods
 *
 * @package ShoppingOS\Abstracts
 * @since 2.0.0
 */
abstract class Base {
	/**
	 * @var array : will be filled with data from the plugin config class
	 * @see Plugin
	 */
	protected $plugin = [];

	/**
	 * Base constructor.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		$this->plugin = Plugin::init();
	}
}
