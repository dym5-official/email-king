<?php

namespace EmailKing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Data_DB extends Data {

	private $table;

	public function __construct( $fs = true ) {
		global $wpdb;
		$this->table = $wpdb->prefix . 'email_king_wp';
	}

	private function file( $scope, $key ) {
		$scope = $this->norm( $scope );
		$key   = $this->norm( $key );

		return $scope . '/' . $key;
	}

	public function set( $scope, $key, $data ) {
		global $wpdb;

		$file = $this->file( $scope, $key );
		$data = serialize( $data );

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				'INSERT INTO %i (`_key`, `_data`) VALUES (%s, %s) ON DUPLICATE KEY UPDATE `_data`=%s, _updated_at=CURRENT_TIMESTAMP()',
				$this->table,
				$file,
				$data,
				$data
			)
		);

		return $key;
	}

	public function get( $scope, $key, $default_value = null, $ttl = -1, $unserialize = true ) {
		global $wpdb;

		$row = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				'SELECT TIMESTAMPDIFF(SECOND, `_updated_at`, CURRENT_TIMESTAMP()) AS `age`, `_data` FROM %i WHERE `_key`=%s',
				$this->table,
				$this->file( $scope, $key )
			)
		);

		if ( $row && ( $ttl < 0 || ( $row->age <= $ttl ) ) ) {
			if ( $unserialize ) {
				return unserialize( $row->_data );
			}

			return $row->_data;
		}

		return $default_value;
	}

	public function exists( $scope, $key, $ttl = -1 ) {
		global $wpdb;

		$row = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				'SELECT TIMESTAMPDIFF(SECOND, `_updated_at`, CURRENT_TIMESTAMP()) AS `age` FROM %i WHERE `_key`=%s',
				$this->table,
				$this->file( $scope, $key )
			)
		);

		if ( ! $row ) {
			return false;
		}

		if ( $ttl < 0 || $row->age <= $ttl ) {
			return true;
		}

		return false;
	}

	public function rem( $scope, $key ) {
		global $wpdb;

		$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$this->table,
			array(
				'_key' => $this->file( $scope, $key ),
			)
		);

		return true;
	}

	public function age( $scope, $key ) {
		global $wpdb;

		$row = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				'SELECT TIMESTAMPDIFF(SECOND, `_updated_at`, CURRENT_TIMESTAMP()) AS `age` FROM %i WHERE `_key`=%s',
				$this->table,
				$this->file( $scope, $key ),
			)
		);

		if ( $row ) {
			return $row->age;
		}

		return false;
	}

	public function list( $scope ) {
		global $wpdb;

		$scope = $this->norm( $scope );

		if ( ! $scope ) {
			return array();
		}

		$scope .= '/';
		$items  = array();

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				'SELECT `_key` FROM %i WHERE `_key` LIKE %s',
				$this->table,
				$wpdb->esc_like( $scope ) . '%'
			)
		);

		foreach ( $rows as $row ) {
			$key = $row->_key;
			$key = substr( $key, strlen( $scope ) );

			if ( false === strpos( $key, '/' ) ) {
				$items[] = $key;
			}
		}

		return $items;
	}

	public function rmrf( $scope ) {
		global $wpdb;

		$scope = $this->norm( $scope );
		$scope = trim( $scope, '/' );

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				'DELETE FROM %i WHERE _key LIKE %s',
				$this->table,
				$wpdb->esc_like( $scope ) . '%'
			)
		);
	}

	public function append( $scope, $key, $data, $separator = "\n" ) {
		global $wpdb;

		$file = $this->file( $scope, $key );

		if ( false === $separator ) {
			$separator = '';
		}

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				'INSERT INTO %i(`_key`, `_data`) VALUES (%s, %s) ON DUPLICATE KEY UPDATE `_data` = CONCAT(_data, %s, %s)',
				$this->table,
				$file,
				$data,
				$separator,
				$data,
			)
		);

		return true;
	}
}
