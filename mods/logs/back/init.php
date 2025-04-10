<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function email_king_wp__init_mod_logs() {
	require_once __DIR__ . '/api-handlers.php';
	require_once __DIR__ . '/class-logs.php';

	$app = $GLOBALS['EMAIL_KING'];

	\EmailKing\Mods\Logs::init();

	$app->api->add(
		'default',
		'get_logs',
		'email_king_wp__api_get_logs'
	);

	$app->api->add(
		'default',
		'delete_logs',
		'email_king_wp__api_delete_logs'
	);

	$app->api->add(
		'default',
		'resend_email',
		'email_king_wp__api_resend_email'
	);
}
