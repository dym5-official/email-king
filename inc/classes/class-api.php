<?php

namespace EmailKing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Api {

	private $list = array();

	public function __construct() {
		add_action( 'wp_ajax_email-king', array( $this, 'handle' ) );
		add_action( 'wp_ajax_nopriv_email-king', array( $this, 'handle' ) );
	}

	public function add( $scope, $key, $callback ) {
		if ( ! isset( $this->list[ $scope ] ) ) {
			$this->list[ $scope ] = array();
		}

		$this->list[ $scope ][ $key ] = $callback;
	}

	public function handle() {
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'email-king-api' ) || ! current_user_can( EMAIL_KING__ACCESS_USER_CAP ) ) {
			$this->forbidden( null, true );
		}

		$content_type = isset( $_SERVER['CONTENT_TYPE'] ) ? sanitize_key( $_SERVER['CONTENT_TYPE'] ) : '';
		$post_data    = array();
		$get_data     = array();
		$query_string = '';

		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$query_string = sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) );
			$query_string = explode( '?', $query_string, 2 );
			$query_string = isset( $query_string[1] ) ? $query_string[1] : '';
		}

		parse_str( $query_string, $get_data );

		if ( 'applicationjson' === strtolower( $content_type ) ) {
			$json_content = \json_decode( file_get_contents( 'php://input' ), true );

			if ( ! json_last_error() ) {
				$post_data = &$json_content;
			}
		}

		$args = array(
			'GET'  => $get_data,
			'POST' => $post_data,
		);

		$scope    = isset( $_GET['__scope__'] ) ? sanitize_key( $_GET['__scope__'] ) : '*never*';
		$key      = isset( $_GET['__key__'] ) ? sanitize_key( $_GET['__key__'] ) : '*never*';
		$response = $this->exec( $scope, $key, $args );

		header( 'Content-Type: application/json' );

		echo \wp_json_encode( $response );

		exit;
	}

	public function exec( $scope, $key, $args ) {
		$callback = isset( $this->list[ $scope ], $this->list[ $scope ][ $key ] ) ? $this->list[ $scope ][ $key ] : null;

		if ( null !== $callback ) {
			return call_user_func( $callback, $args );
		}

		return $this->notfound();
	}

	private function format_res( $code, $res, $should_exit = false ) {
		$status = $code;

		if ( $should_exit ) {
			header( 'Content-Type: application/json' );

			echo \wp_json_encode(
				array(
					'status'  => $status,
					'payload' => $res,
				)
			);

			exit;
		}

		return array(
			'status'  => $status,
			'payload' => $res,
		);
	}

	public function success( $res = array(), $should_exit = false ) {
		return $this->format_res( 200, $res, $should_exit );
	}

	public function forbidden( $res = array(), $should_exit = false ) {
		return $this->format_res( 403, $res, $should_exit );
	}

	public function notfound( $res = array(), $should_exit = false ) {
		return $this->format_res( 404, $res, $should_exit );
	}

	public function internal_error( $res = array(), $should_exit = false ) {
		return $this->format_res( 500, $res, $should_exit );
	}

	public function bad( $res = array(), $should_exit = false ) {
		return $this->format_res( 400, $res, $should_exit );
	}

	public function res( $res = array(), $should_exit = false ) {
		return $this->format_res( 200, $res, $should_exit );
	}

	public function unprocessable( $res = array(), $should_exit = false ) {
		return $this->format_res( 422, $res, $should_exit );
	}

	public function send( $status, $res = array(), $should_exit = false ) {
		return $this->format_res( $status, $res, $should_exit );
	}
}
