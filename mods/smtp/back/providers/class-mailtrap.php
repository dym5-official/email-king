<?php

namespace EmailKing\Mods\SMTP\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mailtrap extends Provider {

	public $provider = 'mailtrap';

	protected function validate_form_( $payload ) {
		// API Key
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
		// @DOC: https://api-docs.mailtrap.io/docs/mailtrap-api-docs/67f1d70aeb62c-send-email-including-templates
		$headers = array(
			'Api-Token' => $this->args['profile']['apikey'],
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
		$reply_to    = $this->format_emails_rfc( $this->phpmailer->getReplyToAddresses() );
		$attachments = $this->get_attachments( 'base64' );

		if ( $cc ) {
			$payload['cc'] = $cc;
		}

		if ( $bcc ) {
			$payload['bcc'] = $bcc;
		}

		if ( $reply_to ) {
			$payload['headers'] = array(
				'Reply-To' => $reply_to,
			);
		}

		if ( $attachments ) {
			$payload['attachments'] = array();

			foreach ( $attachments as $attachment ) {
				$payload['attachments'][] = array(
					'content'     => $attachment['content'],
					'type'        => $attachment['mime'],
					'filename'    => $attachment['name'],
					'disposition' => $attachment['disposition'],
					'content_id'  => $attachment['content_id'],
				);
			}
		}

		$response = $this->http->call(
			array(
				'url'     => 'https://send.api.mailtrap.io/api/send',
				'method'  => 'POST',
				'type'    => 'application/json',
				'data'    => $payload,
				'headers' => $headers,
				'expects' => 'json',
				'curl'    => array(
					CURLOPT_ENCODING       => '',
					CURLOPT_FOLLOWLOCATION => true,
				),
			)
		);

		return $this->format_return( $response );
	}

	private function format_return( $response ) {
		$status = $response['status'];

		if ( $status < 50 ) {
			$result['message'] = $response['message'];
		} elseif ( $status >= 200 && $status < 300 ) {
			$result['success'] = true;
			$result['message'] = 'Email sent successfully.';

			if ( isset( $response['body']['message_ids'][0] ) ) {
				$result['id'] = $response['body']['message_ids'][0];
			}
		} else {
			$body    = $response['body'];
			$message = '';

			if ( is_string( $body ) ) {
				$message = $body;
			}

			if ( is_array( $body ) && isset( $body['errors'] ) ) {
				$key = key( $body['errors'] );

				if ( is_string( $body['errors'][ $key ] ) ) {
					$message = $body['errors'][ $key ];
				}
			}

			if ( empty( $message ) ) {
				$message = 'Something went wrong';
			}

			$result['message'] = $message;
		}

		if ( 401 === $status ) {
			$result['message'] .= ' (maybe the API key is invalid or expired)';
		}

		return $result;
	}
}
