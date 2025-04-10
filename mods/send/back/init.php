<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use EmailKing\Mods\Send;

function email_king_wp__init_mod_send() {
	require_once __DIR__ . '/class-send.php';

	$app = $GLOBALS['EMAIL_KING'];

	$app->api->add(
		'default',
		'send_email',
		array( Send::class, 'send' )
	);
}
