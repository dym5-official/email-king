<?php

namespace EmailKing\Mods\SMTP\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mailgun extends Provider {

	public $provider = 'mailgun';

	protected function validate_form_( $payload ) {
		// API Key
		if ( ! isset( $payload['apikey'] ) || ! is_string( $payload['apikey'] ) || empty( $payload['apikey'] ) ) {
			$this->errors['apikey'] = 'Required';
		} else {
			$this->data['apikey'] = trim( $payload['apikey'] );
		}

		// mailgunregion

		// Domain name
		if ( ! isset( $payload['domain'] ) || ! is_string( $payload['domain'] ) || empty( $payload['domain'] ) ) {
			$this->errors['domain'] = 'Required';
		} else {
			$this->data['domain'] = trim( $payload['domain'] );
		}

		// Region
		if ( ! isset( $payload['mailgunregion'] ) || ! is_string( $payload['mailgunregion'] ) || empty( $payload['mailgunregion'] ) ) {
			$this->errors['mailgunregion'] = 'Required';
		} elseif ( ! in_array( $payload['mailgunregion'], array( 'us', 'eu' ), true ) ) {
			$this->errors['mailgunregion'] = 'Invalid';
		} else {
			$this->data['mailgunregion'] = trim( $payload['mailgunregion'] );
		}
	}

	protected function format_list_item_() {
		return array(
			'apikey'        => $this->args['profile']['apikey'],
			'domain'        => $this->args['profile']['domain'],
			'mailgunregion' => $this->args['profile']['mailgunregion'],
		);
	}

	protected function send_() {
		$url  = 'https://api';
		$url .= 'eu' === $this->args['profile']['mailgunregion'] ? '.eu' : '';
		$url .= '.mailgun.net/v3/';
		$url .= $this->args['profile']['domain'];
		$url .= '/messages';

		$headers = array(
			// phpcs:ignore
			'Authorization' => 'Basic ' . base64_encode( 'api:' . $this->args['profile']['apikey'] ),
		);

		$body_key = 'html';

		if ( ! $this->is_html() ) {
			$body_key = 'text';
		}

		$payload = array(
			'from'    => sprintf( '%s <%s>', $this->phpmailer->FromName, $this->phpmailer->From ),
			'to'      => $this->format_emails_rfc( $this->phpmailer->getToAddresses() ),
			'subject' => $this->phpmailer->Subject,
			$body_key => $this->phpmailer->Body,
		);

		$cc          = $this->format_emails_rfc( $this->phpmailer->getCcAddresses() );
		$bcc         = $this->format_emails_rfc( $this->phpmailer->getBccAddresses() );
		$reply_to    = $this->format_emails_rfc( $this->phpmailer->getReplyToAddresses() );
		$attachments = $this->get_attachments( 'path' );

		if ( $cc ) {
			$payload['cc'] = $recipients;
		}

		if ( $bcc ) {
			$payload['bcc'] = $recipients;
		}

		if ( $reply_to ) {
			$recipients = explode( ',', $recipients );
			$recipient  = trim( $recipients[0] );

			$payload['h:Reply-To'] = $recipient;
		}

		if ( $attachments ) {
			foreach ( $attachments as $i => $attachment ) {
				$payload[ 'attachment[' . $i . ']' ] = new \CURLFILE( $attachment['path'] );
			}
		}

		$response = $this->http->call(
			array(
				'url'     => $url,
				'method'  => 'POST',
				'headers' => $headers,
				'data'    => $payload,
				'expects' => 'json',

				'curl'    => array(
					CURLOPT_ENCODING => '',
					CURLOPT_TIMEOUT  => 300,
				),
			)
		);

		return $this->format_return( $response );
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
			$result['id']      = $response['body']['id'];
		} else {
			$error = isset( $response['body'] ) ? $response['body'] : array();
			$error = is_array( $error ) ? $error : array();

			$result['message'] = isset( $error['message'] ) ? $error['message'] : 'Something went wrong';
		}

		if ( 401 === $status ) {
			$result['message'] .= ' (maybe the API key is invalid or expired)';
		}

		return $result;
	}
}
