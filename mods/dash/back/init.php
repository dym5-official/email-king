<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use EmailKing\Mods\Dash;

function email_king_wp__init_mod_dash() {
	require_once __DIR__ . '/class-dash.php';

	$app = $GLOBALS['EMAIL_KING'];

	$app->api->add(
		'default',
		'get_dashboard',
		array( Dash::class, 'get' )
	);
}
