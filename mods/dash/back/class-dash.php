<?php

namespace EmailKing\Mods;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use EmailKing\Mods\SMTP;
use EmailKing\Mods\SMTP\Settings;

class Dash {

	public static function get() {
		$main = email_king_wp();
		$from = strtotime( '-6 days' );
		$to   = time();

		$result = array(
			'emails_enabled' => ! Settings::is_emails_disabled(),
			'count_failed'   => $main->data->get( 'email/smtp', 'incr-failed', 0 ),
			'count_sent'     => $main->data->get( 'email/smtp', 'incr-sent', 0 ),
			'valid_domain'   => Settings::is_site_allowed(),
			'chart'          => self::genchart( $from, $to ),
		);

		return $main->api->success( $result );
	}

	private static function genchart( $from, $to ) {
		return array(
			'stat' => self::figs( self::days( $from, $to ) ),
			'from' => strtoupper( gmdate( 'd F, Y', $from ) ),
			'to'   => strtoupper( gmdate( 'd F, Y', $to ) ),
		);
	}

	private static function days( $from, $to ) {
		$from = gmdate( 'Ymd', $from );
		$to   = gmdate( 'Ymd', $to );
		$day  = $from;
		$days = array();
		$max  = 1000;
		$incr = 0;

		while ( $day !== $to ) {
			++$incr;

			$days[] = $day;
			$day    = gmdate( 'Ymd', strtotime( '+1 day', strtotime( $day ) ) );

			if ( $incr >= $max ) {
				break;
			}
		}

		$days[] = $to;

		return $days;
	}

	private static function figs( $days ) {
		$data  = email_king_wp( 'data' );
		$stat  = $data->get( 'email/smtp', 'stat', array() );
		$chart = array();

		foreach ( $days as $day ) {
			$label = gmdate( 'F d', strtotime( $day, 0 ) );
			$label = strtoupper( $label );
			$figs  = array( 0, 0, 0 );

			if ( isset( $stat[ $day ] ) ) {
				$figs   = $stat[ $day ];
				$figs[] = array_sum( $figs );
			}

			$chart[] = array(
				'label' => $label,
				'figs'  => $figs,
			);
		}

		return $chart;
	}
}
