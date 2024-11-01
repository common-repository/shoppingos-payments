<?php
namespace ShoppingOS\Traits;

/**
 * The singleton skeleton trait to instantiate the class only once
 *
 * @package ShoppingOS\Traits
 * @since 2.0.0
 */
trait SendSuccessFail {
	/**
	 * Updates the failed status on ShoppingOS Service side
	 *
	 * @since 1.0.6
	 * @return array
	 */
	public function send_fail_callback( $params, $refund_flag ) {

		$request_args = [
			'headers' => [
				'App-Id'     => $params['app_id'],
				'App-Secret' => $params['app_secret'],
			],
			'body' => [
				'environment' => $params['environment'],
				'request_id'  => sanitize_text_field( $params['request-id'] ),
				'message'     => sanitize_text_field( $params['message'] ),
				'refund_flag' => $refund_flag,
			],
			'method' => 'PUT',
			'timeout' => 10,
			/**
			 * @todo use filter `http_request_timeout` with low priority to
			 * make imposible overriding timeout by other plugins.
			 */
		];

		// Send PUT request fail_endpoint defined in constructor
		$response = wp_remote_request( $this->endpoints->endpoint( 'fail' ), $request_args );

		return $response;
	}

	/**
	 * Updates the status on ShoppingOS Service side
	 *
	 * @since 1.0.6
	 * @return array
	 */
	public function send_success_callback( $params, $refund_flag ) {

		$request_args = [
			'headers' => [
				'App-Id'     => $params['app_id'],
				'App-Secret' => $params['app_secret'],
			],
			'body' => [
				'environment' => $params['environment'],
				'token_id'    => sanitize_text_field( $params['tokenId'] ),
				'request_id'  => sanitize_text_field( $params['request-id'] ),
				'signature'   => sanitize_text_field( $params['signature'] ),
				'refund_flag' => $refund_flag,
			],
			'method' => 'PUT',
			'timeout' => 10,
			/**
			 * @todo use filter `http_request_timeout` with low priority to
			 * make imposible overriding timeout by other plugins.
			 */
		];

		// Send PUT request token_endpoint defined in constructor
		$response = wp_remote_request( $this->endpoints->endpoint( 'token' ), $request_args );

		return $response;
	}
}
