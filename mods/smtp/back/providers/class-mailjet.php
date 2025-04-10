<?php

namespace EmailKing\Mods\SMTP\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mailjet extends Provider {

	public $provider = 'mailjet';

	protected function validate_form_( $payload ) {
		// API Key
		if ( ! isset( $payload['apikey'] ) || ! is_string( $payload['apikey'] ) || empty( $payload['apikey'] ) ) {
			$this->errors['apikey'] = 'Required';
		} else {
			$this->data['apikey'] = trim( $payload['apikey'] );
		}

		// Secret Key
		if ( ! isset( $payload['secretkey'] ) || ! is_string( $payload['secretkey'] ) || empty( $payload['secretkey'] ) ) {
			$this->errors['secretkey'] = 'Required';
		} else {
			$this->data['secretkey'] = trim( $payload['secretkey'] );
		}
	}

	protected function format_list_item_() {
		return array(
			'apikey'    => $this->args['profile']['apikey'],
			'secretkey' => $this->args['profile']['secretkey'],
		);
	}

	protected function send_() {
		// @DOC: https://dev.mailjet.com/email/guides/send-api-v31/
		$headers = array(
			// phpcs:ignore
			'Authorization' => 'Basic ' . base64_encode( $this->args['profile']['apikey'] . ':' . $this->args['profile']['secretkey'] ),
		);

		$body_key = $this->is_html() ? 'HTMLPart' : 'TextPart';

		$payload = array(
			'From'    => array(
				'Name'  => $this->phpmailer->FromName,
				'Email' => $this->phpmailer->From,
			),
			'To'      => $this->format_emails_arrays( $this->phpmailer->getToAddresses(), 'Email', 'Name' ),
			'Subject' => $this->phpmailer->Subject,
			$body_key => $this->phpmailer->Body,
		);

		$cc  = $this->format_emails_arrays( $this->phpmailer->getCcAddresses(), 'Email', 'Name' );
		$bcc = $this->format_emails_arrays( $this->phpmailer->getBccAddresses(), 'Email', 'Name' );

		if ( $cc ) {
			$payload['Cc'] = $cc;
		}

		if ( $bcc ) {
			$payload['Bcc'] = $bcc;
		}

		// phpcs:ignore
		// $reply_to = $this->format_emails_arrays( array_values( $this->phpmailer->getReplyToAddresses() ) , 'email', 'name');

		$attachments = $this->get_attachments( 'base64' );

		if ( 0 < count( $attachments ) ) {
			$payload['Attachments'] = array();

			foreach ( $attachments as $attachment ) {
				$payload['Attachments'][] = array(
					'ContentType'   => $attachment['mime'],
					'Filename'      => $attachment['name'],
					'Base64Content' => $attachment['content'],
				);
			}
		}

		$response = $this->http->call(
			array(
				'url'     => 'https://api.mailjet.com/v3.1/send',
				'method'  => 'POST',
				'type'    => 'application/json',
				'data'    => array( 'Messages' => array( $payload ) ),
				'headers' => $headers,
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
			$values            = $this->find_values( $response['body'], array( 'MessageID' ) );
			$result['id']      = $values['MessageID'];
		} elseif ( 401 === $status ) {
			$result['message'] = $response['body']['ErrorMessage'];
		} else {
			$values = $this->find_values( $response['body'], array( 'Status', 'ErrorMessage' ) );

			if ( 'error' === $values['Status'] && $values['ErrorMessage'] ) {
				$result['message'] = $values['ErrorMessage'];
			} else {
				$result['message'] = "Status: $status, failed to send email";
			}
		}

		return $result;
	}
}
