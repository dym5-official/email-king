<?php

namespace EmailKing\Mods\SMTP\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Brevo extends Provider {

	public $provider = 'brevo';

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
		// @DOC: https://developers.brevo.com/reference/sendtransacemail
		$headers = array(
			'api-key' => $this->args['profile']['apikey'],
		);

		$payload = array(
			'sender'      => array(
				'name'  => $this->phpmailer->FromName,
				'email' => $this->phpmailer->From,
			),
			'to'          => $this->format_emails_arrays( $this->phpmailer->getToAddresses(), 'email', 'name' ),
			'cc'          => $this->format_emails_arrays( $this->phpmailer->getCcAddresses(), 'email', 'name' ),
			'bcc'         => $this->format_emails_arrays( $this->phpmailer->getBccAddresses(), 'email', 'name' ),
			'subject'     => $this->phpmailer->Subject,
			'htmlContent' => $this->phpmailer->Body,
		);

		$reply_to = $this->format_emails_arrays( array_values( $this->phpmailer->getReplyToAddresses() ), 'email', 'name' );

		if ( 0 !== count( $reply_to ) ) {
			$payload['replyTo'] = $reply_to[0];
		}

		if ( 0 === count( $payload['cc'] ) ) {
			unset( $payload['cc'] );
		}

		if ( 0 === count( $payload['bcc'] ) ) {
			unset( $payload['bcc'] );
		}

		if ( 'text/plain' === $this->phpmailer->ContentType ) {
			$payload['textContent'] = $this->phpmailer->Body;
			$payload['htmlContent'] = $this->get_html_content();
		}

		$attachments = $this->get_attachments( 'base64' );

		if ( 0 !== count( $attachments ) ) {
			foreach ( $attachments as $attachment ) {
				$payload['attachment'][] = array(
					'name'    => $attachment['name'],
					'content' => $attachment['content'],
				);
			}
		}

		$response = $this->http->call(
			array(
				'url'     => 'https://api.brevo.com/v3/smtp/email',
				'method'  => 'POST',
				'type'    => 'application/json',
				'expects' => 'json',
				'headers' => $headers,
				'data'    => $payload,
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
			$result['id']      = $response['body']['messageId'];
		} else {
			$result['message'] = $response['body']['message'];
		}

		if ( 401 === $status ) {
			$result['message'] .= ' (maybe the API key is invalid or expired)';
		}

		return $result;
	}
}
