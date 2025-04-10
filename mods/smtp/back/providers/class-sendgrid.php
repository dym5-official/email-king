<?php

namespace EmailKing\Mods\SMTP\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sendgrid extends Provider {

	public $provider = 'sendgrid';

	protected function validate_form_( $payload ) {
		// Client ID
		if ( ! isset( $payload['apikey'] ) || ! is_string( $payload['apikey'] ) || empty( $payload['apikey'] ) ) {
			$this->errors['apikey'] = 'Required';
		} else {
			$this->data['apikey'] = trim( $payload['apikey'] );
		}
	}

	protected function format_list_item_() {
		$item = array(
			'apikey' => $this->args['profile']['apikey'],
		);

		return $item;
	}

	protected function send_() {
		// @DOC: https://docs.sendgrid.com/api-reference/mail-send/mail-send
		$headers = array(
			'Authorization' => 'Bearer ' . $this->args['profile']['apikey'],
			'Content-Type'  => 'application/json',
		);

		$payload = array(
			'from'             => array(
				'name'  => $this->phpmailer->FromName,
				'email' => $this->phpmailer->From,
			),

			'personalizations' => array(
				array(
					'to'      => $this->format_emails_arrays( $this->phpmailer->getToAddresses(), 'email', 'name' ),
					'subject' => $this->phpmailer->Subject,
				),
			),

			'content'          => array(
				array(
					'type'  => $this->phpmailer->ContentType,
					'value' => $this->phpmailer->Body,
				),
			),
		);

		$cc          = $this->format_emails_arrays( $this->phpmailer->getCcAddresses(), 'email', 'name' );
		$bcc         = $this->format_emails_arrays( $this->phpmailer->getBccAddresses(), 'email', 'name' );
		$reply_to    = $this->format_emails_arrays( $this->phpmailer->getReplyToAddresses(), 'email', 'name' );
		$attachments = $this->get_attachments( 'base64' );

		if ( $cc ) {
			$payload['personalizations']['cc'] = $cc;
		}

		if ( $bcc ) {
			$payload['personalizations']['bcc'] = $bcc;
		}

		if ( $reply_to ) {
			$payload['reply_to_list'] = $reply_to;
		}

		if ( 0 !== count( $attachments ) ) {
			$payload['attachments'] = array();

			foreach ( $attachments as $attachment ) {
				$payload['attachments'][] = array(
					'filename'    => $attachment['name'],
					'content'     => $attachment['content'],
					'type'        => $attachment['mime'],
					'disposition' => $attachment['disposition'],
					'content_id'  => $attachment['content_id'],
				);
			}
		}

		$response = $this->http->call(
			array(
				'url'     => 'https://api.sendgrid.com/v3/mail/send',
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
			$result['message'] = $response['body']['errors'][0]['message'];
		}

		if ( 401 === $status ) {
			$result['message'] = ' (maybe the API key is invalid or expired)';
		}

		return $result;
	}
}
