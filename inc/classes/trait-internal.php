<?php

namespace EmailKing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait Internal {

	public function main( $key = null ) {
		if ( null !== $key ) {
			return $GLOBALS['EMAIL_KING']->$key;
		}

		return $GLOBALS['EMAIL_KING'];
	}
}
