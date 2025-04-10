<?php

namespace EmailKing\Mods\SMTP\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Google extends Provider {

	public $provider  = 'google';
	protected $oauth2 = true;

	protected function validate_form_( $payload, $context ) {
		// Client ID
		if ( ! isset( $payload['clientid'] ) || ! is_string( $payload['clientid'] ) || empty( $payload['clientid'] ) ) {
			$this->errors['clientid'] = 'Required';
		} else {
			$this->data['clientid'] = trim( $payload['clientid'] );
		}

		// Client ID
		if ( ! isset( $payload['clientsecret'] ) || ! is_string( $payload['clientsecret'] ) || empty( $payload['clientsecret'] ) ) {
			$this->errors['clientsecret'] = 'Required';
		} else {
			$this->data['clientsecret'] = trim( $payload['clientsecret'] );
		}

		// Remove token if creds changed
		if ( 'update' === $context && $this->oauth2_needs_auth( true ) ) {
			$this->data['token'] = null;
		}
	}

	public function validate_sender( $payload ) {
		// Validate custom sender
		$this->data['customsender'] = ( isset( $payload['customsender'] ) && '1' === $payload['customsender'] ) ? '1' : '0';

		// Validate sender name
		if ( ! isset( $payload['fromname'] ) || ! is_string( $payload['fromname'] ) || empty( $payload['fromname'] ) ) {
			$this->errors['fromname'] = 'Required';
		} else {
			$this->data['fromname'] = $payload['fromname'];
		}

		$available_emails = $this->get_senderopts()['email']['fixed'];

		// Validate sender email
		if ( ! isset( $payload['fromemail'] ) || ! is_string( $payload['fromemail'] ) || empty( $payload['fromemail'] ) ) {
			$this->errors['fromemail'] = 'Required';
		} elseif ( ! filter_var( $payload['fromemail'], FILTER_VALIDATE_EMAIL ) ) {
			$this->errors['fromemail'] = 'Invalid email';
		} elseif ( ! in_array( $payload['fromemail'], $available_emails, true ) ) {
			$this->errors['fromemail'] = 'Invalid email';
		} else {
			$this->data['fromemail'] = $payload['fromemail'];
		}

		return $this;
	}

	protected function format_list_item_() {
		$item = array(
			'clientid'     => $this->args['profile']['clientid'],
			'clientsecret' => $this->args['profile']['clientsecret'],
			'customsender' => '1',
		);

		return $item;
	}

	public function get_senderopts() {
		$emails = array();
		$me     = $this->me();

		if ( $me ) {
			$emails[] = $me['email'];
		}

		return $this->get_senderopts_(
			array(
				'must'  => true,
				'email' => array( 'fixed' => $emails ),
			)
		);
	}

	public function get_initial_sender_info() {
		$me = $this->me();

		if ( $me ) {
			return array(
				'customsender' => '1',
				'fromname'     => $me['name'],
				'fromemail'    => $me['email'],
			);
		}

		return array();
	}

	public function oauth2_get_auth_url() {
		$nonce = wp_create_nonce( 'set_up_email_google' );

		$params = array(
			'state'         => $this->provider . '-' . $this->args['profile']['_id'] . '-' . $nonce,
			'client_id'     => $this->args['profile']['clientid'],
			'redirect_uri'  => $this->oauth2_get_redirect_uri(),
			'response_type' => 'code',
			'access_type'   => 'offline',

			'scope'         => implode(
				' ',
				array(
					'https://www.googleapis.com/auth/gmail.send',
					'https://www.googleapis.com/auth/userinfo.profile',
					'https://www.googleapis.com/auth/userinfo.email',
				)
			),
		);

		return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query( $params );
	}

	public function oauth2_get_init_token( $code ) {
		$headers = array(
			'Content-Type' => 'application/x-www-form-urlencoded',
		);

		$payload = array(
			'code'          => $code,
			'client_id'     => $this->args['profile']['clientid'],
			'client_secret' => $this->args['profile']['clientsecret'],
			'redirect_uri'  => $this->oauth2_get_redirect_uri(),
			'grant_type'    => 'authorization_code',
		);

		$response = $this->http->call(
			array(
				'url'     => 'https://oauth2.googleapis.com/token',
				'method'  => 'POST',
				'data'    => http_build_query( $payload ),
				'headers' => $headers,
				'expects' => 'json',
			)
		);

		if ( $response['success'] ) {
			$response['body'] = $this->oauth2_format_token( $response['body'] );
		}

		return $response;
	}

	public function oauth2_format_token( $token, $pre = array() ) {
		$token['expires'] = time() + $token['expires_in'];
		return array_merge( $pre, $token );
	}

	public function oauth2_get_profile() {
		return $this->http->call(
			array(
				'url'     => 'https://www.googleapis.com/oauth2/v1/userinfo?alt=json',
				'expects' => 'json',
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->args['profile']['token']['access_token'],
				),
			)
		);
	}

	public function oauth2_refresh_token() {
		$pre_token     = $this->get_profile_prop( 'token' );
		$profile_id    = $this->get_profile_prop( '_id' );
		$refresh_token = $pre_token['refresh_token'];

		$headers = array(
			'Content-Type' => 'application/x-www-form-urlencoded',
		);

		$payload = array(
			'client_id'     => $this->args['profile']['clientid'],
			'client_secret' => $this->args['profile']['clientsecret'],
			'refresh_token' => $refresh_token,
			'grant_type'    => 'refresh_token',
		);

		$response = $this->http->call(
			array(
				'url'     => 'https://oauth2.googleapis.com/token',
				'method'  => 'POST',
				'data'    => http_build_query( $payload ),
				'headers' => $headers,
				'expects' => 'json',
			)
		);

		if ( $response['success'] ) {
			$token     = $this->oauth2_format_token( $response['body'], $pre_token );
			$pre_token = $token;

			$this->set_profile_prop( 'token', $token );
			$this->smtp::update_( $profile_id, compact( 'token' ) );
		}

		return $pre_token['access_token'];
	}

	public function oauth2_get_access_token( $refresh_if_expired = false ) {
		$token        = $this->get_profile_prop( 'token' );
		$access_token = '';

		if ( $token ) {
			$access_token = $token['access_token'];
			$expires_at   = $token['expires'];

			if ( $refresh_if_expired && ( $expires_at - time() ) < 20 ) {
				$access_token = $this->oauth2_refresh_token();
			}
		}

		return $access_token;
	}

	public function oauth2_needs_auth( $compare = true ) {
		$profile = $this->args['profile'];

		if ( $compare ) {
			$pre = $this->args['pre'];

			if ( ! $pre || $profile['clientid'] !== $pre['clientid'] || $profile['clientsecret'] !== $pre['clientsecret'] ) {
				return true;
			}
		}

		if ( ! isset( $profile['token'] ) || ! is_array( $profile['token'] ) ) {
			return true;
		}

		return false;
	}

	protected function send_() {
		$email    = '';
		$token    = $this->oauth2_get_access_token( true );
		$boundary = 'boundary-' . str_shuffle( md5( time() ) );

		$headers = array(
			'Authorization' => 'Bearer ' . $token,
			'Content-Type'  => 'message/rfc822',
		);

		$email .= "From: {$this->phpmailer->FromName} <{$this->phpmailer->From}>\r\n";
		$email .= 'To: ' . $this->format_emails_rfc( $this->phpmailer->getToAddresses() ) . "\r\n";

		$cc       = $this->format_emails_rfc( $this->phpmailer->getCcAddresses() );
		$bcc      = $this->format_emails_rfc( $this->phpmailer->getBccAddresses() );
		$reply_to = $this->format_emails_rfc( $this->phpmailer->getReplyToAddresses() );

		if ( $cc ) {
			$email .= 'Cc: ' . $cc . "\r\n";
		}

		if ( $bcc ) {
			$email .= 'Bcc: ' . $bcc . "\r\n";
		}

		if ( $reply_to ) {
			$reply_to = explode( ',', $reply_to );

			foreach ( $reply_to as $reply_to_ ) {
				$email .= 'Reply-To: ' . trim( $reply_to_ ) . "\r\n";
			}
		}

		$email .= "Subject: {$this->phpmailer->Subject}\r\n";
		$email .= "MIME-Version: 1.0\r\n";
		$email .= 'Content-Type: multipart/mixed; boundary="' . $boundary . '"' . "\r\n";
		$email .= "Content-Disposition: inline\r\n";
		$email .= "\r\n";
		$email .= "--$boundary\r\n";
		$email .= "Content-Type: {$this->phpmailer->ContentType}; charset={$this->phpmailer->CharSet}\r\n";
		$email .= "Content-Transfer-Encoding: 8bit\r\n";
		$email .= "\r\n";
		$email .= $this->phpmailer->Body;
		$email .= "\r\n\r\n";

		foreach ( $this->get_attachments( 'base64' ) as $attachment ) {
			$email .= "--$boundary\r\n";
			$email .= 'Content-Type: ' . $attachment['mime'] . "\r\n";
			$email .= 'Content-Disposition: attachment; filename="' . $attachment['name'] . '"' . "\r\n";
			$email .= 'Content-Transfer-Encoding: base64' . "\r\n";
			$email .= "\r\n";
			$email .= $attachment['content'];
			$email .= "\r\n\r\n";
		}

		$email .= "--$boundary--";

		$response = $this->http->call(
			array(
				'url'     => 'https://www.googleapis.com/upload/gmail/v1/users/me/messages/send',
				'method'  => 'POST',
				'data'    => $email,
				'headers' => $headers,
				'expects' => 'json',
			)
		);

		return self::format_return( $response );
	}

	private function format_return( $response ) {
		$result = array(
			'success' => false,
			'message' => 'Unknown',
			'status'  => $response['status'],
		);

		$status = $response['status'];

		if ( $status < 50 ) {
			$result['message'] = $response['message'];
		} elseif ( $status >= 200 && $status < 300 ) {
			$result['success'] = true;
			$result['message'] = 'Email sent successfully.';
			$result['id']      = null;
		} else {
			$result['message'] = $response['body']['error']['message'];
		}

		if ( 401 === $status ) {
			$result['message'] .= ' (maybe the access key is invalid or expired)';
		}

		return $result;
	}
}
