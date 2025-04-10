<?php

namespace EmailKing\Mods\SMTP\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use EmailKing\Internal;
use EmailKing\Mods\SMTP\Settings;

class Provider {

	use Internal;

	protected $errors    = array();
	protected $data      = array();
	protected $oauth2    = false;
	protected $args      = array();
	protected $phpmailer = null;
	protected $settings  = null;
	protected $smtp      = \EmailKing\Mods\SMTP::class;
	protected $http      = null;

	protected $senderopts = array(
		'must'  => false,
		'name'  => array(
			'any'  => true,
			'opts' => array(),
		),
		'email' => array(
			'fixed'  => false,
			'domain' => array(
				'any'  => true,
				'opts' => array(),
			),
		),
	);

	public function __construct( $args = array() ) {
		$this->args = array_merge(
			array(
				'profile' => array(),
				'pre'     => array(),
			),
			$args
		);

		$this->settings = Settings::get();
		$this->http     = $this->main( 'http' );
	}

	public function set( $key, $value ) {
		$this->args[ $key ] = $value;
	}

	public function set_profile( $profile ) {
		$this->args['profile'] = $profile;
	}

	public function set_profile_prop( $key, $value ) {
		$this->args['profile'][ $key ] = $value;
	}

	public function get_profile_prop( $key, $default_value = null ) {
		return isset( $this->args['profile'][ $key ] ) ? $this->args['profile'][ $key ] : $default_value;
	}

	public function get_profile() {
		return $this->args['profile'];
	}

	public function get_id() {
		return $this->get_profile_prop( '_id' );
	}

	protected function validate_name( $payload ) {
		if ( ! isset( $payload['name'] ) || ! is_string( $payload['name'] ) || empty( $payload['name'] ) ) {
			$this->errors['name'] = 'Required';
		} else {
			$this->data['name'] = trim( $payload['name'] );
		}
	}

	public function validate_from( $payload, $context = 'add' ) {
		$this->validate_name( $payload );
		$this->validate_form_( $payload, $context );

		if ( 'add' === $context ) {
			$this->data['created_at'] = time();
			$this->data['updated_at'] = time();
			$this->data['created_by'] = get_current_user_id();
			$this->data['updated_by'] = get_current_user_id();
		}

		if ( 'update' === $context ) {
			$this->data['updated_at'] = time();
			$this->data['updated_by'] = get_current_user_id();
		}

		return $this;
	}

	protected function get_senderopts_( $custom = array() ) {
		return array_merge( $this->senderopts, $custom );
	}

	public function get_senderopts() {
		return $this->senderopts;
	}

	public function validate_sender( $payload ) {
		// Validate custom sender
		$this->data['customsender'] = ( isset( $payload['customsender'] ) && '1' === $payload['customsender'] ) ? '1' : '0';

		if ( '1' === $this->data['customsender'] ) {
			// Validate sender name
			if ( ! isset( $payload['fromname'] ) || ! is_string( $payload['fromname'] ) || empty( $payload['fromname'] ) ) {
				$this->errors['fromname'] = 'Required';
			} else {
				$this->data['fromname'] = $payload['fromname'];
			}

			// Validate sender email
			if ( ! isset( $payload['fromemail'] ) || ! is_string( $payload['fromemail'] ) || empty( $payload['fromemail'] ) ) {
				$this->errors['fromemail'] = 'Required';
			} elseif ( ! filter_var( $payload['fromemail'], FILTER_VALIDATE_EMAIL ) ) {
				$this->errors['fromemail'] = 'Invalid email';
			} else {
				$this->data['fromemail'] = $payload['fromemail'];
			}
		}

		return $this;
	}

	public function get_initial_sender_info() {
		return array();
	}

	public function get_data() {
		$this->data['provider'] = $this->provider;
		return $this->data;
	}

	public function get_errors() {
		return $this->errors;
	}

	public function get_changed() {
		return $this->changed;
	}

	public function me() {
		if ( isset( $this->args['profile']['me'] ) && is_array( $this->args['profile']['me'] ) ) {
			return $this->args['profile']['me'];
		}

		return false;
	}

	public function format_list_item( $custom = array() ) {
		$item = array(
			'_id'          => $this->args['profile']['_id'],
			'provider'     => $this->provider,
			'auth_url'     => $this->oauth2_get_auth_url_if_needs( false ),
			'name'         => $this->args['profile']['name'],
			'default'      => isset( $this->args['profile']['default'] ) ? $this->args['profile']['default'] : '0',
			'customsender' => isset( $this->args['profile']['customsender'] ) ? $this->args['profile']['customsender'] : '0',
			'fromname'     => isset( $this->args['profile']['fromname'] ) ? $this->args['profile']['fromname'] : '',
			'fromemail'    => isset( $this->args['profile']['fromemail'] ) ? $this->args['profile']['fromemail'] : '',
			'senderopts'   => $this->get_senderopts(),
		);

		if ( method_exists( $this, 'format_list_item_' ) ) {
			$item = array_merge( $item, $this->format_list_item_() );
		}

		return array_merge( $item, $custom );
	}

	public function oauth2_get_auth_url_if_needs( $compare = true ) {
		if ( ! method_exists( $this, 'oauth2_needs_auth' ) ) {
			return false;
		}

		if ( $this->oauth2_needs_auth( $compare ) ) {
			return $this->oauth2_get_auth_url();
		}

		return false;
	}

	public function is_oauth2() {
		return $this->oauth2;
	}

	public function oauth2_get_redirect_uri() {
		return get_admin_url();
	}

	public function oauth2_needs_auth() {
		return false;
	}

	public function set_phpmailer( &$phpmailer ) {
		$this->phpmailer = $phpmailer;
		return $this;
	}

	public function get_phpmailer() {
		return $this->phpmailer;
	}

	protected function format_emails_arrays( $emails, $email_key, $name_key ) {
		foreach ( $emails as $i => $email ) {
			$emails[ $i ] = array(
				$email_key => $email[0],
			);

			if ( isset( $email[1] ) && $email[1] ) {
				$emails[ $i ][ $name_key ] = $email[1];
			}
		}

		return $emails;
	}

	protected function format_emails_rfc( $emails, $implode = true ) {
		$formatted = array();

		foreach ( $emails as $i => $email ) {
			$str = '';

			if ( isset( $email[1] ) && $email[1] ) {
				$str .= $email[1];
			}

			$str .= $str ? ' <' . $email[0] . '>' : $email[0];

			$formatted[] = $str;
		}

		if ( $implode ) {
			return implode( ', ', $formatted );
		}

		return $formatted;
	}

	protected function is_html() {
		return 'text/plain' !== strtolower( $this->phpmailer->ContentType );
	}

	protected function get_html_content() {
		$html = $this->phpmailer->Body;
		// phpcs:disable
		// $html = trim( $html );
		// $html = preg_replace('"\b(https?://\S+)"', '<a href="$1" target="_blank">$1</a>', $$html);
		// phpcs:enable
		return $html;
	}

	public function send() {
		if ( ! method_exists( $this, 'send_' ) ) {
			return array(
				'success' => false,
				'message' => 'Mailer not found for this provider',
				'status'  => 0,
			);
		}

		$result = $this->send_();

		if ( is_array( $result ) && isset( $result['id'] ) ) {
			do_action(
				'wp_mail_result_extra',
				array(
					'id' => $result['id'],
				)
			);
		}

		return $result;
	}

	protected function get_attachments( $type ) {
		$attachments = $this->phpmailer->getAttachments();
		$result      = array();

		foreach ( $attachments as $attachment ) {
			$content     = '';
			$disposition = $attachment[6];

			if ( 'base64' === $type ) {
				$content = base64_encode( file_get_contents( $attachment[0] ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			}

			if ( ! in_array( $attachment, array( 'attachment', 'inline' ), true ) ) {
				$disposition = 'inline';
			}

			$result[] = array(
				'name'        => $attachment[2],
				'mime'        => trim( (string) $attachment[4] ),
				'content'     => $content,
				'path'        => $attachment[0],
				'disposition' => $disposition,
				'content_id'  => trim( (string) $attachment[7] ),
			);
		}

		return $result;
	}

	public function refresh() {
		$id      = $this->get_id();
		$profile = $this->smtp::get( $id );

		$this->set_profile( $profile );
	}

	protected function find_values( $arr, $keys ) {
		$result = array();

		foreach ( $keys as $key ) {
			$result[ $key ] = null;
		}

		$callback = function ( &$value, $key ) use ( $keys, &$result ) {
			if ( ! is_numeric( $key ) && in_array( $key, $keys, true ) && ! $result[ $key ] ) {
				$result[ $key ] = $value;
			}
		};

		array_walk_recursive( $arr, $callback );

		return $result;
	}
}
