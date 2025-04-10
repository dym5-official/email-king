<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function email_king_wp__add_email_send_api() {
	$perms = array( array( 'manage_smtp', EMAIL_KING__SCOPE_EMAIL ) );

	email_king_wp( 'api' )->add(
		EMAIL_KING__SCOPE_EMAIL,
		array(
			'key'      => 'send_email',
			'title'    => 'Send email',
			'desc'     => 'Manually send email.',
			'perms'    => $perms,
			'callback' => array( \EmailKing\Send::class, 'send' ),
		)
	);
}

add_action( 'email_king_wp__init_api', 'email_king_wp__add_email_send_api' );
