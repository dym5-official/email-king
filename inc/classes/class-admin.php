<?php

namespace EmailKing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin {

	public static function init() {
		return new self();
	}

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function add_admin_menu() {
		$icon = EMAIL_KING__PLUGIN_URL . '/static/icon.svg';

		add_menu_page(
			'Email King',                      // Page title.
			'Email King',                      // Menu title.
			EMAIL_KING__ACCESS_USER_CAP,       // Capability.
			'email-king',                      // Menu slug.
			array( $this, 'admin_page' ),      // Callback.
			$icon,                             // Icon URL.
			80,                                // Position.
		);
	}

	public function enqueue_assets( $hook ) {
		if ( 'toplevel_page_email-king' !== $hook ) {
			return;
		}

		wp_enqueue_script( 'email-king-admin', EMAIL_KING__PLUGIN_URL . '/static/admin.js', array(), EMAIL_KING__VERSION, true );
		wp_enqueue_style( 'email-king-admin', EMAIL_KING__PLUGIN_URL . '/static/admin.css', array(), EMAIL_KING__VERSION );
	}

	public function admin_page() {
		include EMAIL_KING__PLUGIN_DIR . '/templates/admin.php';
	}
}
