<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EMAIL_KING__PLUGIN_DIR', dirname( __DIR__ ) );
define( 'EMAIL_KING__ACCESS_USER_CAP', 'edit_posts' );
define( 'EMAIL_KING__VERSION', '1.0.0' );
define( 'EMAIL_KING__BASENAME', basename( EMAIL_KING__PLUGIN_DIR ) );
define( 'EMAIL_KING__PLUGIN_URL', rtrim( plugin_dir_url( EMAIL_KING__PLUGIN_FILE ), '/' ) );
define( 'EMAIL_KING__URL', admin_url( 'admin-ajax.php?action=email-king-app' ) );
define( 'EMAIL_KING__API_FAILED_EMAIL_NOTIFICATION', 'https://api.dym5.com/ek/v1/email-failed' );
define( 'EMAIL_KING__API_UPDATE_INFO', 'https://api.dym5.com/ek/v1/info' );

spl_autoload_register(
	function ( $class_name ) {
		$class_name = ltrim( $class_name, '\\' );
		$class_name = str_replace( '\\', '/', $class_name );
		$class_name = str_replace( '_', '-', $class_name );
		$class_name = ltrim( $class_name, '-' );
		$class_name = strtolower( $class_name );

		$namespace = 'emailking/';

		if ( 0 === strpos( $class_name, $namespace ) ) {
			$class_name = substr( $class_name, strlen( $namespace ) );
			$file1      = EMAIL_KING__PLUGIN_DIR . '/inc/classes/class-' . $class_name . '.php';
			$file2      = EMAIL_KING__PLUGIN_DIR . '/inc/classes/trait-' . $class_name . '.php';

			if ( file_exists( $file1 ) ) {
				require_once $file1;
				return;
			}

			if ( file_exists( $file2 ) ) {
				require_once $file2;
			}
		}
	}
);

$GLOBALS['EMAIL_KING'] = new \EmailKing\Main( EMAIL_KING__PLUGIN_FILE );

function email_king_wp( $key = null ) {
	if ( null !== $key ) {
		return $GLOBALS['EMAIL_KING']->$key;
	}

	return $GLOBALS['EMAIL_KING'];
}

require_once EMAIL_KING__PLUGIN_DIR . '/mods/index.php';
