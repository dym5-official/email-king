<?php

namespace EmailKing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class App {

	use Internal;

	public static function init() {
		return new self();
	}

	public function __construct() {
		add_action( 'email_king_wp__enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'email_king_wp__head', array( $this, 'email_king_wp__head' ) );
		add_action( 'wp_ajax_email-king-app', array( $this, 'page' ) );
		add_action( 'wp_ajax_nopriv_email-king-app', array( $this, 'page' ) );
	}

	public function page() {
		if ( current_user_can( EMAIL_KING__ACCESS_USER_CAP ) ) {
			include EMAIL_KING__PLUGIN_DIR . DIRECTORY_SEPARATOR . implode( DIRECTORY_SEPARATOR, array( 'templates', 'app.php' ) );
		}
	}

	public function enqueue_assets() {
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_script( 'email-king-bundle', EMAIL_KING__PLUGIN_URL . '/assets/bundle.js', array(), EMAIL_KING__VERSION, true );
		wp_enqueue_style( 'email-king-bundle', EMAIL_KING__PLUGIN_URL . '/assets/bundle.css', array(), EMAIL_KING__VERSION );

		// Inline scripts.
		wp_add_inline_script(
			'email-king-bundle',
			sprintf(
				'window.VARS = %s;',
				wp_json_encode(
					array(
						'ajaxurl'  => esc_url( admin_url( 'admin-ajax.php' ) ),
						'_wpnonce' => wp_create_nonce( 'email-king-api' ),
						'url'      => esc_url( EMAIL_KING__PLUGIN_URL ),
						'siteurl'  => esc_url( site_url() ),
						'cburl'    => get_admin_url(),
					)
				)
			),
			'before'
		);
	}

	public function email_king_wp__head() {
		do_action( 'email_king_wp__enqueue_scripts' );

		wp_styles()->do_items();
		wp_scripts()->do_items();
	}
}
