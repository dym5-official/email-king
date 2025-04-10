<?php

namespace EmailKing\Mods\SMTP\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SendLayer extends Provider {

	public $provider = 'sendlayer';

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
		// @DOC: https://sendlayer.com/docs/api/

		$headers = array(
			'Authorization' => 'Bearer ' . $this->args['profile']['apikey'],
		);

		$type     = 'HTML';
		$body_key = 'HTMLContent';

		if ( ! $this->is_html() ) {
			$type     = 'Plain';
			$body_key = 'PlainContent';
		}

		$payload = array(
			'From'        => array(
				'name'  => $this->phpmailer->FromName,
				'email' => $this->phpmailer->From,
			),
			'To'          => $this->format_emails_arrays( $this->phpmailer->getToAddresses(), 'email', 'name' ),
			'Subject'     => $this->phpmailer->Subject,
			'ContentType' => $type,
			$body_key     => $this->phpmailer->Body,
		);

		$cc          = $this->format_emails_arrays( $this->phpmailer->getCcAddresses(), 'email', 'name' );
		$bcc         = $this->format_emails_arrays( $this->phpmailer->getBccAddresses(), 'email', 'name' );
		$repply_to   = $this->format_emails_arrays( $this->phpmailer->getReplyToAddresses(), 'email', 'name' );
		$attachments = $this->get_attachments( 'base64' );

		if ( $cc ) {
			$payload['CC'] = $cc;
		}

		if ( $bcc ) {
			$payload['BCC'] = $recipients;
		}

		if ( $repply_to ) {
			$payload['ReplyTo'] = $recipients;
		}

		if ( $attachments ) {
			$payload['Attachments'] = array();

			foreach ( $attachments as $attachment ) {
				$payload['Attachments'][] = array(
					'Filename'    => $attachment['name'],
					'Content'     => $attachment['content'],
					'Type'        => $attachment['mime'],
					'Disposition' => $attachment['disposition'],
					'ContentId'   => $attachment['content_id'],
				);
			}
		}

		$response = $this->http->call(
			array(
				'url'     => 'https://console.sendlayer.com/api/v1/email',
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
			$result['id']      = $response['body']['MessageID'];
		} else {
			$errors = isset( $response['body']['Errors'] ) ? $response['body']['Errors'] : array();
			$errors = is_array( $errors ) ? $errors : array();

			if ( count( $errors ) > 0 ) {
				$result['message'] = $errors[0]['Message'];
			} else {
				$result['message'] = 'Something went wrong';
			}
		}

		if ( 401 === $status ) {
			$result['message'] .= ' (maybe the API key is invalid or expired)';
		}

		return $result;
	}
}
