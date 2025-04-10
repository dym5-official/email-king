<?php

namespace EmailKing\Mods\SMTP\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Postmark extends Provider {

	public $provider = 'postmark';

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
		// @DOC: https://postmarkapp.com/developer/api/email-api

		$headers = array(
			'X-Postmark-Server-Token' => $this->args['profile']['apikey'],
		);

		$body_key = $this->is_html() ? 'HtmlBody' : 'TextBody';

		$payload = array(
			'From'    => sprintf( '%s <%s>', $this->phpmailer->FromName, $this->phpmailer->From ),
			'To'      => $this->format_emails_rfc( $this->phpmailer->getToAddresses() ),
			'Subject' => $this->phpmailer->Subject,
			$body_key => $this->phpmailer->Body,
		);

		$cc          = $this->format_emails_rfc( $this->phpmailer->getCcAddresses() );
		$bcc         = $this->format_emails_rfc( $this->phpmailer->getBccAddresses() );
		$reply_to    = $this->format_emails_rfc( $this->phpmailer->getReplyToAddresses() );
		$attachments = $this->get_attachments( 'base64' );

		if ( $cc ) {
			$payload['Cc'] = $cc;
		}

		if ( $bcc ) {
			$payload['Bcc'] = $bcc;
		}

		if ( $reply_to ) {
			$payload['ReplyTo'] = $reply_to;
		}

		if ( $attachments ) {
			$payload['Attachments'] = array();

			foreach ( $attachments as $attachment ) {
				$payload['Attachments'][] = array(
					'Name'        => $attachment['name'],
					'Content'     => $attachment['content'],
					'ContentType' => $attachment['mime'],
				);
			}
		}

		$response = $this->http->call(
			array(
				'url'     => 'https://api.postmarkapp.com/email',
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
			$result['id']      = $response['body']['MessageID'];
		} else {
			$result['message'] = $response['body']['Message'];
		}

		if ( 401 === $status ) {
			$result['message'] .= ' (maybe the API key is invalid or expired)';
		}

		return $result;
	}
}
