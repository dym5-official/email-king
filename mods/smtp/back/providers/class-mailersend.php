<?php

namespace EmailKing\Mods\SMTP\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MailerSend extends Provider {

	public $provider = 'mailersend';

	protected function validate_form_( $payload ) {
		// Client ID
		if ( ! isset( $payload['apikey'] ) || ! is_string( $payload['apikey'] ) || empty( $payload['apikey'] ) ) {
			$this->errors['apikey'] = 'Required';
		} else {
			$this->data['apikey'] = trim( $payload['apikey'] );
		}
	}

	protected function format_list_item_() {
		return array(
			'apikey' => $this->args['profile']['apikey'],
		);
	}

	protected function send_() {
		// @DOC: https://developers.mailersend.com/general.html
		$headers = array(
			'Authorization' => 'Bearer ' . $this->args['profile']['apikey'],
		);

		$body_key = $this->is_html() ? 'html' : 'text';

		$payload = array(
			'from'    => array(
				'name'  => $this->phpmailer->FromName,
				'email' => $this->phpmailer->From,
			),
			'to'      => $this->format_emails_arrays( $this->phpmailer->getToAddresses(), 'email', 'name' ),
			'subject' => $this->phpmailer->Subject,
			$body_key => $this->phpmailer->Body,
		);

		$cc          = $this->format_emails_arrays( $this->phpmailer->getCcAddresses(), 'email', 'name' );
		$bcc         = $this->format_emails_arrays( $this->phpmailer->getBccAddresses(), 'email', 'name' );
		$reply_to    = $this->format_emails_arrays( array_values( $this->phpmailer->getReplyToAddresses() ), 'email', 'name' );
		$attachments = $this->get_attachments( 'base64' );

		if ( $cc ) {
			$payload['cc'] = $cc;
		}

		if ( $bcc ) {
			$payload['bcc'] = $bcc;
		}

		if ( $reply_to ) {
			$payload['reply_to'] = $reply_to[0];
		}

		if ( 0 < count( $attachments ) ) {
			$payload['attachments'] = array();

			foreach ( $attachments as $attachment ) {
				$payload['attachments'][] = array(
					'content'     => $attachment['content'],
					'disposition' => $attachment['disposition'],
					'filename'    => $attachment['name'],
					'id'          => $attachment['content_id'],
				);
			}
		}

		$response = $this->http->call(
			array(
				'url'     => 'https://api.mailersend.com/v1/email',
				'method'  => 'POST',
				'data'    => $payload,
				'headers' => $headers,
				'type'    => 'application/json',
				'expects' => 'json',
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
		} else {
			$body    = $response['body'];
			$body    = is_array( $body ) ? $body : array();
			$message = isset( $body['message'] ) ? $body['message'] : '';
			$errors  = isset( $body['errors'] ) ? $body['errors'] : array();
			$key     = key( $errors );

			if ( $key ) {
				$message = $errors[ $key ][ key( $errors[ $key ] ) ];
			}

			$result['message'] = $message ? $message : 'Something went wrong';
		}

		if ( 401 === $status ) {
			$result['message'] .= ' (maybe the API key is invalid or expired)';
		}

		return $result;
	}
}
