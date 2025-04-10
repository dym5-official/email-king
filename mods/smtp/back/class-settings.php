<?php

namespace EmailKing\Mods\SMTP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use EmailKing\Mods\SMTP;

class Settings {

	private static $settings = null;

	public static function get( $prop = null, $default_value = null ) {
		if ( null === self::$settings ) {
			$default = array(
				'senderenable'        => '0',
				'disableemails'       => '0',
				'enablefallbacks'     => '0',
				'sendername'          => '',
				'senderemail'         => '',
				'fallbacks'           => array(),
				'onlysites'           => '0',
				'enablenotifications' => '0',
				'sites'               => array(),
			);

			$settings = email_king_wp( 'data' )->get( 'email/smtp', 'settings', array() );
			$settings = is_array( $settings ) ? $settings : array();

			self::$settings = array_merge( $default, $settings );
		}

		if ( null !== $prop ) {

			if ( isset( self::$settings[ $prop ] ) ) {
				return self::$settings[ $prop ];
			}

			return $default_value;

		}

		return self::$settings;
	}

	public static function latest() {
		self::$settings = null;
		return self::get();
	}

	public static function is_emails_disabled( $check_site = true ) {
		if ( $check_site && ! self::is_site_allowed() ) {
			return true;
		}

		return self::get( 'disableemails', '0' ) === '1';
	}

	public static function is_default_sender_enabled() {
		return self::get( 'senderenable', '0' ) === '1';
	}

	public static function get_default_sender( $prop = null, $default_value = '' ) {
		$sender = array(
			'name'  => self::get( 'sendername', '' ),
			'email' => self::get( 'senderemail', '' ),
		);

		if ( null !== $prop ) {

			if ( isset( $sender[ $prop ] ) ) {
				return $sender[ $prop ];
			}

			return $default_value;
		}

		return $sender;
	}

	public static function is_fallback_enabled() {
		return self::get( 'enablefallbacks', '0' ) === '1';
	}

	public static function get_fallback_profiles() {
		$result             = array();
		$profiles           = self::get( 'fallbacks', array() );
		$profiles           = is_array( $profiles ) ? $profiles : array();
		$default_profile_id = SMTP::get_default( true );

		foreach ( $profiles as $profile_id ) {
			if ( SMTP::exists( $profile_id ) && $profile_id !== $default_profile_id ) {
				$result[] = $profile_id;
			}
		}

		return $result;
	}

	public static function is_send_only_sites_enabled() {
		return self::get( 'onlysites', '0' ) === '1';
	}

	public static function get_send_only_sites() {
		$sites = self::get( 'sites', array() );
		$sites = is_array( $sites ) ? $sites : array();

		return $sites;
	}

	public static function is_site_allowed() {
		if ( self::is_send_only_sites_enabled() ) {
			$current_site = SMTP::clean_site( \site_url() );
			return in_array( self::get_send_only_sites(), $current_site, true );
		}

		return true;
	}

	public static function is_notifications_enabled() {
		return self::get( 'enablenotifications', '0' ) === '1';
	}
}
