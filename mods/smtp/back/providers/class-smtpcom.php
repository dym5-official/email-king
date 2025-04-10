<?php

namespace EmailKing\Mods\SMTP\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SMTPCom extends Provider {

	public $provider = 'smtpcom';

	protected function validate_form_( $payload ) {
		// API Key
		if ( ! isset( $payload['apikey'] ) || ! is_string( $payload['apikey'] ) || empty( $payload['apikey'] ) ) {
			$this->errors['apikey'] = 'Required';
		} else {
			$this->data['apikey'] = trim( $payload['apikey'] );
		}
	}

	protected function get_channels() {
		$channels = $this->get_profile_prop( 'channels', array() );
		$channels = is_array( $channels ) ? $channels : array();

		return $channels;
	}

	protected function format_list_item_() {
		return array(
			'apikey'       => $this->args['profile']['apikey'],
			'recverify'    => true,
			'verified'     => count( $this->get_channels() ) > 0,

			'verification' => array(
				'title' => 'API key needs verification, click here to verify',
				'desc'  => 'The API key you have entered needs to be verified to get available channels.',
			),
		);
	}

	public function get_senderopts() {
		return $this->get_senderopts_(
			array(
				'must'  => true,
				'email' => array(
					'fixed' => array_keys( $this->get_channels() ),
				),
			)
		);
	}

	public function verify() {
		$headers = array(
			'Authorization' => 'Bearer ' . $this->args['profile']['apikey'],
		);

		$response = $this->http->call(
			array(
				'method'  => 'GET',
				'url'     => 'https://api.smtp.com/v4/channels',
				'headers' => $headers,
				'expects' => 'json',
			)
		);

		$error   = '';
		$status  = $response['status'];
		$success = $status >= 200 && $status < 300;

		if ( ! $success ) {
			if ( $status < 50 ) {
				$error = $response['message'];
			} elseif ( 401 === $status ) {
				$error = 'Invalid API key';
			} else {
				$error = \wp_json_encode( $response['body']['data'] );
			}

			return array( 400, $error );
		}

		$items    = $response['body']['data']['items'];
		$channels = array();

		foreach ( $items as $item ) {
			if ( 'active' !== $item['status'] ) {
				continue;
			}

			$channels[ $item['smtp_username'] ] = $item['name'];
		}

		if ( 0 === count( $channels ) ) {
			$this->smtp::update_(
				$this->get_id(),
				array(
					'channels'     => $channels,
					'fromname'     => '',
					'fromemail'    => '',
					'customsender' => '1',
				)
			);

			return array( 400, 'You don\'t have active senders' );
		}

		$fromemail    = key( $channels );
		$fromname     = $channels[ $fromemail ];
		$customsender = '1';

		$this->smtp::update_( $this->get_id(), compact( 'channels', 'fromemail', 'fromname', 'customsender' ) );
		$this->refresh();

		return array( 200, $this->format_list_item() );
	}

	protected function send_() {
		$headers = array(
			'Authorization' => 'Bearer ' . $this->args['profile']['apikey'],
		);

		$channels  = $this->get_channels();
		$fromemail = $this->phpmailer->From;
		$channel   = $channels[ $fromemail ];

		$payload = array(
			'channel'    => $channel,
			'originator' => array(
				'from' => array(
					'name'    => $this->phpmailer->FromName,
					'address' => $this->phpmailer->From,
				),
			),
			'recipients' => array(
				'to' => $this->format_emails_arrays( $this->phpmailer->getToAddresses(), 'address', 'name' ),
			),
			'subject'    => $this->phpmailer->Subject,
			'body'       => array(
				'parts' => array(
					array(
						'type'    => $this->phpmailer->ContentType,
						'charset' => $this->phpmailer->CharSet,
						'content' => $this->phpmailer->Body,
					),
				),
			),
		);

		$cc          = $this->format_emails_arrays( $this->phpmailer->getCcAddresses(), 'address', 'name' );
		$bcc         = $this->format_emails_arrays( $this->phpmailer->getBccAddresses(), 'address', 'name' );
		$reply_to    = $this->format_emails_arrays( $this->phpmailer->getReplyToAddresses(), 'address', 'name' );
		$attachments = $this->get_attachments( 'base64' );

		if ( $cc ) {
			$payload['recipients']['cc'] = $cc;
		}

		if ( $bcc ) {
			$payload['recipients']['bcc'] = $bcc;
		}

		if ( $reply_to ) {
			$payload['originator']['reply_to'] = $reply_to[ key( $recipients ) ];
		}

		if ( 0 < count( $attachments ) ) {
			$payload['body']['attachments'] = array();

			foreach ( $attachments as $attachment ) {
				$payload['body']['attachments'][] = array(
					'type'        => $attachment['mime'],
					'disposition' => $attachment['disposition'],
					'filename'    => $attachment['name'],
					'cid'         => $attachment['content_id'],
					'content'     => $attachment['content'],
				);
			}
		}

		$response = $this->http->call(
			array(
				'url'     => 'https://api.smtp.com/v4/messages',
				'method'  => 'POST',
				'type'    => 'application/json',
				'data'    => $payload,
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
			$response_message  = $response['body']['data']['message'];
			$response_message  = explode( 'msg_id: ', $response_message );

			if ( 2 === count( $response_message ) ) {
				$response_message = $response_message[1];
				$response_message = trim( $response_message );

				$result['id'] = $response_message;
			}
		} else {
			$result['message'] = $response['body']['message'];
		}

		if ( 401 === $status ) {
			$result['message'] .= ' (maybe the API key is invalid or expired)';
		}

		return $result;
	}
}
