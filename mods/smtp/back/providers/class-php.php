<?php

namespace EmailKing\Mods\SMTP\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PHP extends Provider {

	public $provider = 'php';

	public function send_() {
		try {
			$this->phpmailer->send();

			return array(
				'success' => true,
				'message' => 'Email sent successfully.',
				'status'  => 200,
			);
		} catch ( \PHPMailer\PHPMailer\Exception $e ) {
			return array(
				'success' => false,
				'message' => $e->getMessage(),
				'status'  => 0,
			);
		}
	}
}
