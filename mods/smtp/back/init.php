<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function email_king_wp__init_mod_smtp() {
	require_once __DIR__ . '/api-handlers.php';
	require_once __DIR__ . '/load-classes.php';
	require_once __DIR__ . '/class-mailer.php';
	require_once __DIR__ . '/class-smtp.php';

	$app = $GLOBALS['EMAIL_KING'];

	\EmailKing\Mods\SMTP::init();

	$app->api->add(
		'default',
		'activation',
		array( \EmailKing\Mods\SMTP::class, 'activation' )
	);

	$app->api->add(
		'default',
		'get_smtp_profiles',
		'email_king_wp__api_get_smtp_profiles'
	);

	$app->api->add(
		'default',
		'manage_smtp_profiles',
		'email_king_wp__api_manage_smtp_profiles'
	);

	$app->api->add(
		'default',
		'smtp_send_test_email',
		'email_king_wp__api_smtp_send_test_email'
	);

	$app->api->add(
		'default',
		'smtp_settings',
		'email_king_wp__api_smtp_update_settings'
	);
}
