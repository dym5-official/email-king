<?php

use EmailKing\Mods\Logs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function email_king_wp__api_get_logs( $args ) {
	$page    = isset( $args['GET']['page'] ) ? $args['GET']['page'] : 1;
	$keyword = isset( $args['GET']['keyword'] ) ? $args['GET']['keyword'] : '';
	$keyword = is_string( $keyword ) ? trim( $keyword ) : '';

	return call_user_func_array(
		array( email_king_wp( 'api' ), 'send' ),
		Logs::get_logs( (int) $page, $keyword )
	);
}

function email_king_wp__api_resend_email( $args ) {
	return call_user_func_array(
		array( email_king_wp( 'api' ), 'send' ),
		Logs::resend_email( $args['POST'] )
	);
}

function email_king_wp__api_delete_logs( $args ) {
	$ids     = isset( $args['POST']['ids'] ) ? $args['POST']['ids'] : array();
	$ids     = is_array( $ids ) ? $ids : array();
	$page    = isset( $args['POST']['page'] ) ? $args['POST']['page'] : null;
	$keyword = isset( $args['POST']['keyword'] ) ? $args['POST']['keyword'] : '';
	$keyword = is_string( $keyword ) ? $keyword : '';
	$keyword = trim( $keyword );

	return call_user_func_array(
		array( email_king_wp( 'api' ), 'send' ),
		Logs::delete( $ids, $page, $keyword )
	);
}
