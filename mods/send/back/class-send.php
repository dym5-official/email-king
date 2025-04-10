<?php

namespace EmailKing\Mods;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Send {

	public static function normalize_email_address( $value ) {
		$value     = trim( $value );
		$email     = $value;
		$name      = '';
		$with_name = false;
		$open_tag  = strpos( $value, '<' );
		$close_tag = strpos( $value, '>' );

		if ( $open_tag && $close_tag && $open_tag < $close_tag ) {
			$email     = substr( $value, $open_tag + 1, strlen( $value ) - $open_tag - 2 );
			$name      = substr( $value, 0, $open_tag );
			$name      = trim( $name );
			$with_name = true;
		}

		if ( $with_name && ! preg_match( '/^[a-zA-Z.]+(?:[\s-][a-zA-Z.]+)*$/', $name ) ) {
			return false;
		}

		if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			return false;
		}

		if ( $with_name ) {
			return sprintf( '%s <%s>', $name, $email );
		}

		return $email;
	}

	public static function normalize_recipients( $value ) {
		$emails  = array();
		$values  = explode( ',', $value );
		$invalid = '';

		foreach ( $values as $value ) {
			$item = self::normalize_email_address( $value );

			if ( false === $item ) {
				$invalid = $value;
				break;
			}

			$emails[] = $item;
		}

		$empty   = ! $invalid && 0 === count( $emails );
		$invalid = $invalid;

		return (object) compact( 'invalid', 'emails', 'empty' );
	}

	public static function send( $args ) {
		$errors = array();

		$to = self::normalize_recipients( $args['POST']['to'] ?? '' );

		if ( $to->empty || $to->invalid ) {
			$errors['to'] = $to->empty ? 'Required' : sprintf( 'Invalid "%s"', $to->invalid );
		}

		$cc = self::normalize_recipients( $args['POST']['cc'] ?? '' );

		if ( $cc->invalid ) {
			$errors['cc'] = sprintf( 'Invalid "%s"', $cc->invalid );
		}

		$bcc = self::normalize_recipients( $args['POST']['bcc'] ?? '' );

		if ( $bcc->invalid ) {
			$errors['bcc'] = sprintf( 'Invalid "%s"', $bcc->invalid );
		}

		$subject = $args['POST']['subject'] ?? '';
		$subject = trim( $subject );

		$html = $args['POST']['html'] ?? '';
		$html = trim( $html );

		if ( empty( $html ) ) {
			$errors['html'] = 'Email content is required';
		}

		if ( 0 !== count( $errors ) ) {
			return email_king_wp( 'api' )->unprocessable( $errors );
		}

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
		);

		if ( ! $cc->empty ) {
			$headers[] = 'Cc: ' . implode( ', ', $cc->emails );
		}

		if ( ! $bcc->empty ) {
			$headers[] = 'Bcc: ' . implode( ', ', $bcc->emails );
		}

		$sent    = wp_mail( $to->emails, $subject, $html, $headers );
		$message = $sent ? 'Email sent successfully' : 'Failed to send email, please check email logs for more detail.';

		return email_king_wp( 'api' )->send( 200, compact( 'sent', 'message' ) );
	}
}
