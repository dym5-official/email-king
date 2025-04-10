<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function email_king_wp__init_free() {
	define( 'EMAIL_KING__PLUGIN_BASENAME', plugin_basename( EMAIL_KING__PLUGIN_FILE ) );

	if ( defined( 'EMAIL_KING__PRO' ) ) {
		function email_king_wp__show_pro_installed() {
			echo '<tr class="plugin-update-tr active">';
			echo '<td colspan="12" class="plugin-update colspanchange">';
			echo '<div class="wpemailking_proavl" style="margin: 0 18px 18px 40px;border-radius:4px;padding:6px 10px;background-color:rgba(165,95,61,1);color:#fff;">';
			echo 'The pro version of <strong>Email King</strong> is installed. You can deactivate or remove the free plugin.';
			echo '</div>';
			echo '</td>';
			echo '</tr>';
		}

		add_action( 'after_plugin_row_' . EMAIL_KING__PLUGIN_BASENAME, 'email_king_wp__show_pro_installed', 1000 );

		return;
	}

	function email_king_wp__add_buy_link( $links, $file ) {
		global $plugin_name;

		if ( EMAIL_KING__PLUGIN_BASENAME === $file ) {
			$links[] = sprintf( '<a target="_blank" style="color:#a55f3d;font-weight:bold;" href="https://dym5.com/wordpress/plugins/email-king/?ref=fplist">Buy PRO ↗</a>' );
		}

		return $links;
	}

	add_filter( 'plugin_action_links', 'email_king_wp__add_buy_link', 10, 2 );

	require_once __DIR__ . '/plugin.php';
}

add_action( 'plugins_loaded', 'email_king_wp__init_free', -75 );

function email_king_wp__plugins_inline_script( $hook ) {
	if ( 'plugins.php' !== $hook ) {
		return;
	}

	wp_enqueue_script( 'email-king-pp', EMAIL_KING__PLUGIN_URL . '/static/plugins.js', array(), EMAIL_KING__VERSION, true );
}

add_action( 'admin_enqueue_scripts', 'email_king_wp__plugins_inline_script', 20 );
