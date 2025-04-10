<?php

namespace EmailKing\Mods\SMTP\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use EmailKing\Mods\SMTP\Settings;

class Disabled extends Provider {

	protected function send_() {
		$message = 'Emails are disabled';

		if ( ! Settings::is_emails_disabled( false ) && ! Settings::is_site_allowed() ) {
			$message = 'Site not allowed';
		}

		return array(
			'success' => false,
			'message' => $message,
			'status'  => 0,
		);
	}
}
