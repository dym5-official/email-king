<?php

namespace EmailKing\Mods\SMTP\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SparkPost extends Provider {

	public $provider = 'sparkpost';

	protected function validate_form_( $payload ) {
		// API Key
		if ( ! isset( $payload['apikey'] ) || ! is_string( $payload['apikey'] ) || empty( $payload['apikey'] ) ) {
			$this->errors['apikey'] = 'Required';
		} else {
			$this->data['apikey'] = trim( $payload['apikey'] );
		}

		// Region
		if ( ! isset( $payload['sparkpostregion'] ) || ! is_string( $payload['sparkpostregion'] ) || empty( $payload['sparkpostregion'] ) ) {
			$this->errors['sparkpostregion'] = 'Required';
		} elseif ( ! in_array( $payload['sparkpostregion'], array( 'us', 'eu' ), true ) ) {
			$this->errors['sparkpostregion'] = 'Invalid';
		} else {
			$this->data['sparkpostregion'] = trim( $payload['sparkpostregion'] );
		}
	}

	protected function format_list_item_() {
		return array(
			'apikey'          => $this->args['profile']['apikey'],
			'sparkpostregion' => $this->args['profile']['sparkpostregion'],
		);
	}

	protected function send_() {
		$url  = 'https://api';
		$url .= 'eu' === $this->args['profile']['sparkpostregion'] ? '.eu' : '';
		$url .= '.sparkpost.com/api/v1';
		$url .= '/transmissions';

		$headers = array(
			'Authorization' => $this->args['profile']['apikey'],
		);

		$body_key = 'html';

		if ( ! $this->is_html() ) {
			$body_key = 'text';
		}

		$payload = array(
			'options'    => array(
				'sandbox'       => false,
				'transactional' => true,
			),
			'recipients' => array(),
		);

		$to_recipients = $this->format_emails_arrays( $this->phpmailer->getToAddresses(), 'email', 'name' );

		foreach ( $to_recipients as $to_recipient ) {
			$payload['recipients'][] = array(
				'address' => $to_recipient,
			);
		}

		$content = array(
			'from'    => sprintf( '%s <%s>', $this->phpmailer->FromName, $this->phpmailer->From ),
			'subject' => $this->phpmailer->Subject,
			$body_key => $this->phpmailer->Body,
		);

		$_headers = array();

		$cc          = $this->format_emails_rfc( $this->phpmailer->getCcAddresses() );
		$bcc         = $this->format_emails_rfc( $this->phpmailer->getBccAddresses() );
		$reply_to    = $this->format_emails_rfc( $this->phpmailer->getReplyToAddresses() );
		$attachments = $this->get_attachments( 'base64' );

		if ( $cc ) {
			$_headers['Cc'] = $cc;
		}

		if ( $bcc ) {
			$_headers['Bcc'] = $bcc;
		}

		if ( $reply_to ) {
			$content['reply_to'] = $reply_to;
		}

		if ( $_headers ) {
			$content['headers'] = $_headers;
		}

		if ( $attachments ) {
			$content['attachments'] = array();

			foreach ( $attachments as $attachment ) {
				$content['attachments'][] = array(
					'type' => $attachment['mime'],
					'name' => $attachment['name'],
					'data' => $attachment['content'],
				);
			}
		}

		$payload['content'] = $content;

		$response = $this->http->call(
			array(
				'url'     => $url,
				'method'  => 'POST',
				'headers' => $headers,
				'data'    => $payload,
				'type'    => 'application/json',
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

			if ( isset( $response['body']['results']['id'] ) ) {
				$result['id'] = $response['body']['results']['id'];
			}
		} else {
			$message = '';
			$body    = $response['body'];

			if ( is_string( $body ) ) {
				$message = $body;
			}

			if ( is_array( $body ) ) {
				$values = $this->find_values( $body, array( 'message' ) );

				if ( $values['message'] && is_string( $values['message'] ) ) {
					$message = $values['message'];
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
