<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use EmailKing\Mods\SMTP;
use EmailKing\Mods\SMTP\Settings;

function email_king_wp__api_get_smtp_profiles() {
	$result = SMTP::list();

	$result[1] = array(
		'admin_email' => get_option( 'admin_email' ),
		'profiles'    => $result[1],
		'settings'    => Settings::get(),
	);

	return call_user_func_array(
		array( email_king_wp()->api, 'send' ),
		$result
	);
}

function email_king_wp__api_manage_smtp_profiles( $args ) {
	$payload = $args['POST'];
	$actions = array( 'create', 'update', 'enable', 'delete', 'configure_sender', 'get_profile', 'verify' );
	$action  = isset( $payload['action'] ) && in_array( $payload['action'], $actions, true ) ? $payload['action'] : 'create';

	if ( 'create' === $action ) {
		return call_user_func_array(
			array( email_king_wp()->api, 'send' ),
			SMTP::add_or_update_profile( null, $payload )
		);
	}

	if ( in_array( $action, $actions, true ) ) {
		$id = isset( $payload['id'] ) ? $payload['id'] : '';

		if ( ! $id || ! is_string( $id ) || strlen( $id ) > 20 || ! SMTP::exists( $id ) ) {
			return email_king_wp()->api->send( 404 );
		}

		if ( 'update' === $action ) {
			return call_user_func_array(
				array( email_king_wp()->api, 'send' ),
				SMTP::add_or_update_profile( $id, $payload )
			);
		}

		if ( 'configure_sender' === $action ) {
			return call_user_func_array(
				array( email_king_wp()->api, 'send' ),
				SMTP::configure_sender( $id, $payload )
			);
		}

		if ( 'get_profile' === $action ) {
			$profile = SMTP::get( $id );
			$profile = SMTP::format_list_item( $profile );

			return email_king_wp()->api->send( 200, $profile );
		}

		if ( 'verify' === $action ) {
			return call_user_func_array(
				array( email_king_wp()->api, 'send' ),
				SMTP::verify( $id )
			);
		}

		if ( 'enable' === $action ) {
			$status = isset( $payload['status'] ) ? $payload['status'] : false;
			SMTP::set_active( $id, $status );
		}

		if ( 'delete' === $action ) {
			SMTP::delete( $id );
		}

		return email_king_wp()->api->send( 200 );
	}

	return email_king_wp()->api->send( 404 );
}

function email_king_wp__api_smtp_send_test_email( $args ) {
	$payload = $args['POST'];

	$recipient = isset( $payload['email'] ) ? $payload['email'] : '';
	$profile   = isset( $payload['profile'] ) ? $payload['profile'] : '';
	$html      = isset( $payload['html'] ) ? $payload['html'] : '';

	return call_user_func_array(
		array( email_king_wp()->api, 'send' ),
		SMTP::send_test_email( $recipient, $profile, $html )
	);
}

function email_king_wp__api_smtp_update_settings( $args ) {
	$payload = $args['POST'];

	return call_user_func_array(
		array( email_king_wp()->api, 'send' ),
		SMTP::update_settings( $payload )
	);
}
