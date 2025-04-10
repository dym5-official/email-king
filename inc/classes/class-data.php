<?php

namespace EmailKing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Data {

	protected function norm( $key ) {
		return is_array( $key ) ? implode( '/', $key ) : $key;
	}

	public function read( $scope, $key, $default_value = null, $ttl = -1 ) {
		return $this->get( $scope, $key, $default_value, $ttl, false );
	}

	public function getd( $scope, $key, $custom = array() ) {
		$item = $this->get( $scope, $key, null );

		if ( is_array( $item ) ) {
			$item = array_merge( $item, $custom );
		}

		return $item;
	}

	public function rand_id( $length = 14 ) {
		$str = 'abcdefghijklmnopqrstuvwxyz0123456789abcdefghijklmnopqrstuvwxyz0123456789';
		$str = str_shuffle( str_shuffle( $str ) );

		return substr( $str, 0, $length );
	}

	public function insert( $scope, $data, $override = array() ) {
		$key = $this->rand_id();

		while ( $this->exists( $scope, $key ) ) {
			$key = $this->rand_id();
		}

		if ( is_array( $data ) ) {
			$data        = array_merge( $data, $override );
			$data['_id'] = $key;
		}

		return $this->set( $scope, $key, $data );
	}

	public function all( $scope ) {
		return array_map(
			function ( $key ) use ( $scope ) {
				return $this->get( $scope, $key );
			},
			$this->list( $scope )
		);
	}

	public function set_arr( $scope, $key, $arr_key, $value ) {
		$arr             = $this->get( $scope, $key, array() );
		$arr[ $arr_key ] = $value;

		return $this->set( $scope, $key, $arr );
	}

	public function merge( $scope, $key, $arr ) {
		return $this->set(
			$scope,
			$key,
			array_merge(
				$this->get( $scope, $key, array() ),
				$arr
			)
		);
	}

	public function patch( $scope, $key, $data ) {
		if ( $this->exists( $scope, $key ) ) {
			return $this->set(
				$scope,
				$key,
				array_merge(
					$this->get( $scope, $key, array() ),
					$data,
				)
			);
		}

		return false;
	}

	public function incr( $scope, $key = 'incr' ) {
		$incr = (int) $this->get( $scope, $key, 0 ) + 1;

		$this->set( $scope, $key, $incr );

		return $incr;
	}
}
