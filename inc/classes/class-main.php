<?php

namespace EmailKing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Main {

	protected $props = array();
	protected $file  = null;

	public function __construct( $file ) {
		$this->file = $file;

		$this->props['data'] = new Data_DB();
		$this->props['http'] = new Http();
		$this->props['api']  = new Api();

		$this->init();
	}

	private function init() {
		/**
		 * Init: Admin
		 */
		Admin::init();

		/**
		 * Init: App
		 */
		App::init();
	}

	public function __get( $prop ) {
		if ( isset( $this->props[ $prop ] ) ) {
			return $this->props[ $prop ];
		}
	}
}
