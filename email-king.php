<?php
/**
 * Plugin Name: Email King
 * Description: Effortlessly ensure consistent and reliable email delivery from your WordPress site.
 * License: GPLv2 or later
 * Version: 1.0.0
 * Author: DYM5
 * Author URI: https://dym5.com
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EMAIL_KING__PLUGIN_FILE', __FILE__ );

require_once __DIR__ . '/inc/load.php';

register_activation_hook(
	__FILE__,
	function () {
		require_once __DIR__ . '/inc/activation.php';
	}
);
