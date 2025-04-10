<?php

namespace EmailKing\Mods\SMTP\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SMTP extends Provider {

	public $provider = 'smtp';

	protected function validate_form_( $payload ) {
		// Validate host
		if ( ! isset( $payload['host'] ) || ! is_string( $payload['host'] ) || empty( $payload['host'] ) ) {
			$this->errors['host'] = 'Required';
		} else {
			$this->data['host'] = trim( $payload['host'] );
		}

		// Validate port
		if ( ! isset( $payload['port'] ) || empty( $payload['port'] ) ) {
			$this->errors['port'] = 'Required';
		} elseif ( ! is_numeric( $payload['port'] ) || strlen( $payload['port'] ) > 5 || strlen( $payload['port'] ) < 2 ) {
			$this->errors['port'] = 'Invalid';
		} else {
			$this->data['port'] = $payload['port'];
		}

		// Validate encryption
		if ( ! isset( $payload['enc'] ) || empty( $payload['enc'] ) || ! in_array( $payload['enc'], array( 'none', 'tls', 'ssl' ), true ) ) {
			$this->errors['enc'] = 'Required';
		} else {
			$this->data['enc'] = $payload['enc'];
		}

		// Validate auto tls
		if ( isset( $payload['autotls'] ) && '1' === $payload['autotls'] ) {
			$this->data['autotls'] = '1';
		}

		// Validate auth
		$this->data['auth'] = ( isset( $payload['auth'] ) && '1' === $payload['auth'] ) ? '1' : '0';

		$this->data['username'] = isset( $payload['username'] ) && is_string( $payload['username'] ) ? trim( $payload['username'] ) : '';
		$this->data['password'] = isset( $payload['password'] ) && is_string( $payload['password'] ) ? trim( $payload['password'] ) : '';

		if ( '1' === $this->data['auth'] ) {
			if ( empty( $this->data['username'] ) ) {
				$this->errors['username'] = 'Required';
			}

			if ( empty( $this->data['password'] ) ) {
				$this->errors['password'] = 'Required';
			}
		}
	}

	protected function format_list_item_() {
		$item = array(
			'host'     => $this->args['profile']['host'],
			'enc'      => $this->args['profile']['enc'],
			'port'     => $this->args['profile']['port'],
			'autotls'  => $this->args['profile']['autotls'],
			'auth'     => $this->args['profile']['auth'],
			'username' => $this->args['profile']['username'],
			'password' => $this->args['profile']['password'],
		);

		return $item;
	}

	protected function send_() {
		$profile = $this->get_profile();

		$this->phpmailer->isSMTP();
		$this->phpmailer->Host     = $profile['host'];
		$this->phpmailer->SMTPAuth = (bool) $profile['auth'];
		$this->phpmailer->Port     = $profile['port'];
		$this->phpmailer->Username = $profile['username'];
		$this->phpmailer->Password = $profile['password'];
		$this->phpmailer->Timeout  = 4;

		if ( in_array( $profile['enc'], array( 'tls', 'ssl' ), true ) ) {
			$this->phpmailer->SMTPSecure = $profile['enc'];
		}

		try {
			$this->phpmailer->send();

			return array(
				'success' => true,
				'message' => 'Email sent successfully.',
				'status'  => 200,
			);
		} catch ( \PHPMailer\PHPMailer\Exception $e ) {
			return array(
				'success' => false,
				'message' => $e->getMessage(),
				'status'  => 0,
			);
		}
	}
}
