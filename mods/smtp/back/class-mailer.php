<?php

namespace EmailKing\Mods\SMTP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use EmailKing\Mods\SMTP;
use EmailKing\Mods\Logs;
use EmailKing\Mods\SMTP\Settings;

class Mailer {

	protected $phpmailer = null;
	protected $profiles  = null;
	protected $zombie    = false;

	public static function instance( &$phpmailer, $profiles ) {
		return new self( $phpmailer, $profiles );
	}

	public function __construct( &$phpmailer, $profiles ) {
		$this->phpmailer = $phpmailer;
		$this->profiles  = $profiles;
	}

	public function send() {
		foreach ( $this->profiles as $i => $profile_id ) {
			$provider = $this->provider( $profile_id );
			$last     = count( $this->profiles ) === ( $i + 1 );
			$zombie   = $this->zombie;

			$this->zombie = false;

			if ( ! $zombie && $provider ) {
				Logs::set_current_profile( $provider->get_profile() );

				$result = $provider->send();
				$sent   = $result['success'];
				$failed = ! $sent;

				if ( $sent ) {
					return true;
				}

				if ( $failed ) {
					$this->failed( $last, $result );
				}
			}

			if ( $zombie || ! $provider ) {
				Logs::set_current_profile( $provider );

				$this->failed(
					$last,
					array(
						'success' => false,
						'message' => 'Provider not found',
						'status'  => 0,
					)
				);
			}
		}
	}

	public function failed( $should_throw, $result ) {
		if ( $should_throw ) {
			// phpcs:ignore
			throw new \PHPMailer\PHPMailer\Exception( $result['message'] );
		}

		$error = new \WP_Error( 'wp_mail_failed', $result['message'], array() );

		do_action( 'email_king_wp__email_failed', $error );
	}

	private function provider( $profile_id ) {
		if ( 'disabled' === $profile_id ) {
			return SMTP::provider(
				array(
					'profile' => array(
						'provider' => 'disabled',
						'name'     => 'Disabled',
					),
				)
			);
		}

		$provider = null;

		if ( 'php' === $profile_id ) {
			$provider = SMTP::provider(
				array(
					'profile' => array(
						'provider' => 'php',
						'name'     => 'PHP',
					),
				)
			);
		}

		$profile = SMTP::get( $profile_id );

		if ( $profile ) {
			$this->zombie = isset( $profile['zombie'] ) && $profile['zombie'];

			if ( $this->zombie ) {
				return $profile;
			}
		}

		if ( $profile ) {
			$provider = SMTP::provider(
				array(
					'profile' => $profile,
				)
			);
		}

		if ( $provider ) {
			$phpmailer = clone $this->phpmailer;
			$sender    = $this->get_sender( $profile, $phpmailer );

			if ( $sender ) {
				$sender = \apply_filters( 'email_king_wp__email_sender', $sender, $phpmailer, $profile );
				$phpmailer->setFrom( $sender->email, $sender->name, false );
			}

			$provider->set_phpmailer( $phpmailer );
		}

		return $provider;
	}

	private function get_sender( $profile, &$phpmailer ) {
		if ( is_array( $profile ) && isset( $profile['customsender'] ) && 1 === (int) $profile['customsender'] ) {
			return (object) array(
				'name'  => $profile['fromname'],
				'email' => $profile['fromemail'],
			);
		}

		if ( is_array( $profile ) && Settings::is_default_sender_enabled() ) {
			return (object) Settings::get_default_sender();
		}

		return (object) array(
			'name'  => $phpmailer->FromName, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			'email' => $phpmailer->From, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		);
	}
}
