<?php

namespace EmailKing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Http {

	public function call( $args ) {
		$method        = isset( $args['method'] ) ? strtoupper( $args['method'] ) : 'GET';
		$url           = $args['url'];
		$headers       = isset( $args['headers'] ) ? $args['headers'] : array();
		$data          = isset( $args['data'] ) ? $args['data'] : false;
		$parse_as_json = false;
		$content_type  = isset( $args['type'] ) ? $args['type'] : '';
		$expects       = isset( $args['expects'] ) ? $args['expects'] : '';
		$curl_options  = isset( $args['curl'] ) ? $args['curl'] : array();

		if ( $content_type ) {
			$headers['Content-Type'] = $content_type;

			if ( 0 === strpos( strtolower( $content_type ), 'application/json' ) ) {
				$data = \wp_json_encode( $data );
			}
		}

		if ( 'json' === $expects ) {
			$parse_as_json = true;
		}

		$options = array(
			'method'       => $method,
			'headers'      => $headers,
			'curl_options' => $curl_options,
			'httpversion'  => '1.1',
		);

		if ( 'GET' !== $method && false !== $data ) {
			$options['body'] = $data;
		}

		$response    = \wp_remote_request( $url, $options );
		$status_code = \wp_remote_retrieve_response_code( $response );
		$message     = '';
		$res_headers = \wp_remote_retrieve_headers( $response );
		$body        = \wp_remote_retrieve_body( $response );

		if ( \method_exists( $res_headers, 'getAll' ) ) {
			$res_headers = $res_headers->getAll();
		}

		if ( \is_wp_error( $response ) ) {
			$status_code = 0;
			$message     = $response->get_error_message();

			if ( preg_match( '/^cURL error (-?\d+): /', $message, $matches ) ) {
				$status_code = (int) $matches[1];
				$message     = preg_replace( '/^cURL error (-?\d+): /', '', $message );
			}
		}

		if ( $parse_as_json ) {
			$json_body = \json_decode( $body, true );

			if ( ! \json_last_error() ) {
				$body = $json_body;
			}
		}

		return array(
			'success' => $status_code >= 200 && $status_code < 300,
			'status'  => $status_code,
			'message' => $message,
			'headers' => $res_headers,
			'body'    => $body,
		);
	}
}
