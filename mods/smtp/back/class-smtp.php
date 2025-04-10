<?php

namespace EmailKing\Mods;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use EmailKing\Mods\SMTP\Mailer;
use EmailKing\Mods\SMTP\Settings;

class SMTP {

	private static $profiles_data_scope = 'email/smtp/profiles';
	private static $smtp_data_scope     = 'email/smtp';
	private static $initialized         = false;
	private static $settings            = null;
	private static $use_profile_initial = null;
	private static $use_profile         = null;
	private static $fallback_profiles   = array();
	private static $sending_test_email  = false;
	private static $test_email_profile  = false;
	private static $ignore_fallbacks    = false;
	private static $email_atts          = null;
	private static $providers           = array();

	public static function init() {
		if ( self::$initialized ) {
			return;
		}

		self::$providers = array(
			'disabled'   => \EmailKing\Mods\SMTP\Providers\Disabled::class,
			'php'        => \EmailKing\Mods\SMTP\Providers\PHP::class,
			'brevo'      => \EmailKing\Mods\SMTP\Providers\Brevo::class,
			'google'     => \EmailKing\Mods\SMTP\Providers\Google::class,
			'sendgrid'   => \EmailKing\Mods\SMTP\Providers\Sendgrid::class,
			'smtp'       => \EmailKing\Mods\SMTP\Providers\SMTP::class,
			'postmark'   => \EmailKing\Mods\SMTP\Providers\Postmark::class,
			'sendlayer'  => \EmailKing\Mods\SMTP\Providers\SendLayer::class,
			'mailgun'    => \EmailKing\Mods\SMTP\Providers\Mailgun::class,
			'smtpcom'    => \EmailKing\Mods\SMTP\Providers\SMTPCom::class,
			'mailjet'    => \EmailKing\Mods\SMTP\Providers\Mailjet::class,
			'mailersend' => \EmailKing\Mods\SMTP\Providers\MailerSend::class,
			'mailtrap'   => \EmailKing\Mods\SMTP\Providers\Mailtrap::class,
			'sparkpost'  => \EmailKing\Mods\SMTP\Providers\SparkPost::class,
		);

		self::$initialized         = true;
		self::$use_profile         = self::get_default();
		self::$use_profile_initial = self::$use_profile;

		\add_action( 'phpmailer_init', array( self::class, 'set_up_phpmailer' ), PHP_INT_MAX );
		\add_filter( 'wp_mail_from_name', array( self::class, 'set_up_send_from_name' ), PHP_INT_MAX );
		\add_filter( 'wp_mail_from', array( self::class, 'set_up_send_from_email' ), PHP_INT_MAX );
		\add_action( 'wp_mail_failed', array( self::class, 'wp_mail_failed' ), PHP_INT_MAX );
		\add_action( 'wp_mail_succeeded', array( self::class, 'wp_mail_succeeded' ), PHP_INT_MAX );

		self::verify_oauth2();
	}

	public static function provider( $args ) {
		$provider = self::$providers[ $args['profile']['provider'] ];
		$provider = new $provider( $args );

		return $provider;
	}

	public static function add_or_update_profile( $id, $payload ) {
		$args = array(
			'pre'     => $id ? self::get( $id ) : null,
			'profile' => $payload,
		);

		$context  = $id ? 'update' : 'add';
		$provider = self::provider( $args )->validate_from( $payload, $context );
		$errors   = $provider->get_errors();

		if ( $errors ) {
			return array( 422, $errors );
		}

		if ( 'add' === $context ) {
			$id = email_king_wp( 'data' )->insert( self::$profiles_data_scope, $provider->get_data() );
		}

		if ( 'update' === $context ) {
			self::update_( $id, $provider->get_data() );
		}

		$provider->set_profile( self::get( $id ) );

		return array(
			200,
			self::format_list_item(
				$provider->get_profile(),
				array(
					'auth_url' => $provider->oauth2_needs_auth() ? $provider->oauth2_get_auth_url() : false,
				)
			),
		);
	}

	public static function configure_sender( $id, $payload ) {
		$profile  = self::get( $id );
		$provider = self::provider( compact( 'profile' ) )->validate_sender( $payload );
		$errors   = $provider->get_errors();

		if ( $errors ) {
			return array( 422, $errors );
		}

		self::update_( $id, $provider->get_data() );

		return array( 200, self::format_list_item( self::get( $id ) ) );
	}

	public static function update_( $id, $data ) {
		email_king_wp( 'data' )->patch( self::$profiles_data_scope, $id, $data );

		$default_config = self::get_default();

		if ( is_array( $default_config ) && $default_config['_id'] === $id ) {
			email_king_wp( 'data' )->patch( self::$smtp_data_scope, 'default', $data );
		}
	}

	public static function verify_oauth2() {
		// State holds the nonce value and is_strings makes sure that the
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['state'] ) ) {
			$state  = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
			$pieces = explode( '-', $state, 3 );

			if ( 3 > count( $pieces ) ) {
				return;
			}

			$nonce_key   = 'set_up_email_' . $pieces[0];
			$nonce_value = $pieces[2];

			if ( ! wp_verify_nonce( $nonce_value, $nonce_key ) ) {
				return;
			}
		}

		if ( ! isset( $_GET['state'] ) || ! isset( $_GET['code'] ) || ! is_string( $_GET['code'] ) ) {
			return;
		}

		$state  = sanitize_text_field( wp_unslash( $_GET['state'] ) );
		$pieces = explode( '-', $state, 3 );

		$provider = $pieces[0];
		$id       = $pieces[1];
		$profile  = self::get( $id );

		if ( is_array( $profile ) && $provider === $profile['provider'] ) {
			$provider = self::provider( compact( 'profile' ) );
			$code     = sanitize_text_field( wp_unslash( $_GET['code'] ) );
			$response = $provider->oauth2_get_init_token( $code );

			if ( empty( $code ) ) {
				return;
			}

			$token = null;
			$me    = null;

			if ( $response['success'] ) {
				$provider->set_profile_prop( 'token', $response['body'] );

				$token   = $response['body'];
				$profile = $provider->oauth2_get_profile();

				if ( $profile['success'] ) {
					$me = $profile['body'];
				} else {
					$response = $profile;
				}
			}

			header( 'Content-Type: text/html' );

			if ( $response['success'] ) {
				$provider->set_profile_prop( 'me', $me );
				$sender = $provider->get_initial_sender_info();

				self::update_( $id, array_merge( $sender, compact( 'token', 'me' ) ) );

				echo 'Your connection is authorized successfully, close this tab and click on the confirm button in the previous tab.';
				exit;
			}

			echo 'Failed to authorize the connection.<br /><br />';
			echo 'Status: ' . esc_html( $response['status'] ) . '<br />';
			echo 'Message: <br />';

			if ( is_array( $response['body'] ) ) {
				echo \wp_json_encode( $response['body'] );
			} else {
				echo \wp_kses_data( $response['body'] );
			}

			exit;
		}
	}

	public static function verify( $id ) {
		$profile = self::get( $id );

		if ( $profile ) {
			$provider = self::provider( compact( 'profile' ) );

			if ( method_exists( $provider, 'verify' ) ) {
				return $provider->verify();
			}
		}

		return array( 404, 'Not found' );
	}

	public static function list() {
		$list = email_king_wp( 'data' )->all( self::$profiles_data_scope );

		usort(
			$list,
			function ( $a, $b ) {
				return $a['created_at'] > $b['created_at'] ? -1 : 1;
			}
		);

		$list = array_map( array( self::class, 'format_list_item' ), $list );
		$list = array_filter( $list );
		$list = array_values( $list );

		return array( 200, $list );
	}

	public static function format_list_item( $profile, $custom = array() ) {
		$provider = $profile['provider'];

		if ( ! isset( self::$providers[ $provider ] ) ) {
			return false;
		}

		$_provider = self::$providers[ $provider ];
		$_provider = new $_provider( compact( 'profile' ) );

		return array_merge( $_provider->format_list_item(), $custom );
	}

	public static function set_active( $id, $active ) {
		$ids = email_king_wp( 'data' )->list( self::$profiles_data_scope );

		foreach ( $ids as $id_ ) {
			email_king_wp( 'data' )->patch(
				self::$profiles_data_scope,
				$id_,
				array(
					'default' => $id_ === $id ? ( $active ? '1' : '0' ) : '0',
				)
			);
		}

		if ( ! $active ) {
			$active_profile = self::get_default();

			if ( $active_profile && $active_profile['_id'] === $id ) {
				email_king_wp( 'data' )->rem( self::$smtp_data_scope, 'default' );
			}
		}

		if ( $active ) {
			$active_data = email_king_wp( 'data' )->get( self::$profiles_data_scope, $id, null );

			$active_data['activated_by'] = get_current_user_id();
			$active_data['activated_at'] = time();

			email_king_wp( 'data' )->set( self::$smtp_data_scope, 'default', $active_data );
		}
	}

	public static function exists( $id ) {
		if ( 'php' === $id ) {
			return true;
		}

		return email_king_wp( 'data' )->exists( self::$profiles_data_scope, $id );
	}

	public static function get_default( $id_only = false, $at_least_one = false ) {
		$default = email_king_wp( 'data' )->get( self::$smtp_data_scope, 'default', null );

		if ( $id_only && $at_least_one && $default && ! isset( self::$providers[ $default['provider'] ] ) ) {
			return 'php';
		}

		if ( null === $default || ! self::exists( $default['_id'] ) ) {
			return null;
		}

		if ( $id_only ) {
			return $default['_id'];
		}

		return $default;
	}

	public static function delete( $id ) {
		$active = self::get_default();

		if ( $active && $active['_id'] === $id ) {
			email_king_wp( 'data' )->rem( self::$smtp_data_scope, 'default' );
		}

		email_king_wp( 'data' )->rem( self::$profiles_data_scope, $id );
	}

	private static function html_template() {
		ob_start() ?><!DOCTYPE html>
		<html>
		<body>

			<div style="padding: 60px; background-color: #1c1c1c;">
				<table border="0" cellspacing="0" cellpadding="6" width="240" align="center" style="width: 240px; margin: 0 auto; color: #777; border: 1px solid #a55f3d; border-radius: 4px; padding: 20px;">
					<tr><td style="color: #a55f3d; font-size: 28px; font-weight: bold; line-height: 1.5;">Hi 😉</td></tr>
					<tr><td style="font-size: 14px; line-height: 1.5;">If you received this email, your email configuration is working <strong style="color:green;">correctly</strong>.</td></tr>
				</table>
			</div>

		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	private static function plain_template() {
		return "Hi :)\n\nIf you received this email, your email configuration is working correctly.";
	}

	public static function get( $id ) {
		$profile = email_king_wp( 'data' )->get( self::$profiles_data_scope, $id, null );

		if ( $profile && is_array( $profile ) ) {
			$profile['zombie'] = ! isset( self::$providers[ $profile['provider'] ] );
		}

		return $profile;
	}

	public static function send_test_email( $recipient, $profile, $html ) {
		$errors = array();

		// Validate recipient
		if ( empty( $recipient ) || ! is_string( $recipient ) ) {
			$errors['email'] = 'Recipient is required';
		} elseif ( ! filter_var( $recipient, FILTER_VALIDATE_EMAIL ) ) {
			$errors['email'] = 'Invalid email';
		}

		// Validate profile
		if ( empty( $profile ) || ! is_string( $profile ) ) {
			$errors['profile'] = 'Profile is required';
		} elseif ( 'php' !== $profile && 'default' !== $profile && ! self::exists( $profile ) ) {
			$errors['profile'] = 'Profile is invalid';
		}

		// HTML
		$html = '1' === $html;

		if ( 0 !== count( $errors ) ) {
			return array( 422, $errors );
		}

		self::$sending_test_email = true;
		self::$test_email_profile = $profile;

		$headers = $html ? array( 'Content-Type: text/html; charset=UTF-8' ) : array( 'Content-Type: text/plain; charset=UTF-8' );
		$body    = $html ? self::html_template() : self::plain_template();

		wp_mail( $recipient, 'Test email', $body, $headers );
	}

	public static function set_up_send_from_name( $name ) {
		if ( Settings::is_default_sender_enabled() ) {
			$name = Settings::get_default_sender( 'name' );
		}

		return $name;
	}

	public static function set_up_send_from_email( $email ) {
		if ( Settings::is_default_sender_enabled() ) {
			$email = Settings::get_default_sender( 'email' );
		}

		return $email;
	}

	public static function set_up_phpmailer( &$phpmailer ) {
		$profiles = array();
		$disabled = self::$sending_test_email ? false : Settings::is_emails_disabled();

		if ( $disabled ) {
			$profiles[] = 'disabled';
		}

		$testing_with_custom_profile = self::$sending_test_email && 'default' !== self::$test_email_profile;

		if ( $testing_with_custom_profile ) {
			$profiles[] = self::$test_email_profile;
		}

		if ( ! $testing_with_custom_profile && ! $disabled ) {
			$profiles[] = self::get_default( true, true );
		}

		$phpmailer = Mailer::instance( $phpmailer, $profiles );
	}

	public static function clean_site( $url ) {
		$parsed = wp_parse_url( $url );

		if ( ! isset( $parsed['scheme'], $parsed['host'] ) || ! in_array( $parsed['scheme'], array( 'http', 'https' ), true ) ) {
			return false;
		}

		$host = $parsed['host'];
		$path = isset( $parsed['path'] ) ? $parsed['path'] : '';
		$path = preg_replace( '/\/{1,}/', '/', $path );
		$path = rtrim( $path, '/' );
		$url  = $parsed['scheme'] . '://' . $host . $path;

		return $url;
	}

	public static function update_settings( $payload ) {
		$main   = email_king_wp();
		$errors = array();
		$data   = array();
		$pre    = Settings::latest();

		$disableemails = isset( $payload['disableemails'] ) && '1' === $payload['disableemails'] ? '1' : '0';
		$senderenable  = isset( $payload['senderenable'] ) && '1' === $payload['senderenable'] ? '1' : '0';

		$data['senderenable']  = $senderenable;
		$data['disableemails'] = $disableemails;

		if ( '1' === $senderenable ) {
			// Validate name
			if ( isset( $payload['sendername'] ) && is_string( $payload['sendername'] ) && ! empty( $payload['sendername'] ) ) {
				$data['sendername'] = $payload['sendername'];
			} else {
				$errors['sendername'] = 'Name is required';
			}

			// Validate email
			if ( ! isset( $payload['senderemail'] ) || ! is_string( $payload['senderemail'] ) || empty( $payload['senderemail'] ) ) {
				$errors['senderemail'] = 'Email is required';
			} elseif ( ! filter_var( $payload['senderemail'], FILTER_VALIDATE_EMAIL ) ) {
				$errors['senderemail'] = 'Email is invalid';
			} else {
				$data['senderemail'] = $payload['senderemail'];
			}
		}

		if ( '0' === $senderenable ) {
			$data['sendername']  = $pre['sendername'] ?? '';
			$data['senderemail'] = $pre['senderemail'] ?? '';
		}


		if ( '0' === $data['onlysites'] ) {
			$data['sites'] = $pre['sites'] ?? array();
		}

		if ( ! is_array( $data['sites'] ) ) {
			$data['sites'] = array();
		}

		if ( '0' === $data['enablefallbacks'] ) {
			$data['fallbacks'] = $pre['fallbacks'] ?? array();
		}

		if ( ! is_array( $data['fallbacks'] ) ) {
			$data['fallbacks'] = array();
		}

		if ( 0 !== count( $errors ) ) {
			return array( 422, $errors );
		}

		$main->data->set( self::$smtp_data_scope, 'settings', $data );

		return array( 200, $data );
	}

	private static function incr_stat( $index ) {
		$data  = email_king_wp( 'data' );
		$file  = 'stat';
		$scope = self::$smtp_data_scope;
		$stat  = $data->get( $scope, $file, array() );
		$key   = gmdate( 'Ymd' );

		if ( ! isset( $stat[ $key ] ) ) {
			$stat[ $key ] = array( 0, 0 );
		}

		++$stat[ $key ][ $index ];

		$data->set( $scope, $file, $stat );
	}

	private static function get_ip_addr() {
		$keys = array(
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		);

		foreach ( $keys as $key ) {
			if ( isset( $_SERVER[ $key ] ) && ! empty( $key ) ) {
				$addr = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				$addr = trim( $addr[0] );

				if ( empty( $addr ) ) {
					continue;
				}

				return $addr;
			}
		}

		return '';
	}

	public static function wp_mail_failed( $error ) {
		$main = email_king_wp();

		$main->data->incr( self::$smtp_data_scope, 'incr-failed' );

		self::incr_stat( 1 );

		if ( self::$sending_test_email ) {
			email_king_wp( 'api' )->send( 500, $error->get_error_message(), true );
		}
	}

	public static function wp_mail_succeeded() {
		email_king_wp( 'data' )->incr( self::$smtp_data_scope, 'incr-sent' );

		self::incr_stat( 0 );

		if ( self::$sending_test_email ) {
			email_king_wp( 'api' )->send( 200, null, true );
		}
	}
}
