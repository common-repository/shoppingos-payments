<?php
namespace ShoppingOS;

use ShoppingOS\Traits\Singleton;

/**
 * Paths for ShoppingOS Service endpoints
 *
 * The endpoints are responsible for creating Payments via checkout page
 * and Refunds via ShoppingOS Meta Box from the Orders section in admin dashboard.
 *
 * @since   2.0.0
 * @package ShoppingOS
 */
class EndpointsV1 {
	use Singleton;

	protected $base_url    = 'https://service.shoppingos.com';
	protected $api_version = '/api/v1';
	protected $endpoints   = [];

	/**
	 * Class constructor
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function __construct() {
		$this->endpoints['token']  = $this->base_url . $this->api_version . '/payments/token';
		$this->endpoints['fail']   = $this->base_url . $this->api_version . '/payments/fail';
		$this->endpoints['refund'] = $this->base_url . $this->api_version . '/payments/refund';
	}

	/**
	 * Retrieves an endpoint by its name
	 *
	 * @param string $ep Endpoint name.
	 * @return string Endpoint.
	 */
	public function endpoint( string $name ): string {
		if ( ! isset( $this->endpoints[ $name ] ) ) {
			throw new \Exception( "Endpoint doesn't exist", 1 );
		}
		return $this->endpoints[ $name ];
	}
}
