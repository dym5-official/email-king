<?php

namespace EmailKing\Mods;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Logs {

	private static $initialized     = false;
	private static $current_id      = null;
	private static $current_profile = array();

	private static function get_table_name() {
		global $wpdb;

		return "{$wpdb->prefix}ek_email_logs";
	}

	public static function set_current_profile( $profile ) {
		self::$current_profile = $profile;
	}

	public static function init() {
		if ( self::$initialized ) {
			return;
		}

		self::$initialized = true;

		\add_filter( 'wp_mail', array( self::class, 'wp_mail_filter' ), 2, 1 );
		\add_action( 'wp_mail_failed', array( self::class, 'failed' ), 2 );
		\add_action( 'email_king_wp__email_failed', array( self::class, 'failed' ), 2 );
		\add_action( 'wp_mail_succeeded', array( self::class, 'succeeded' ), 2 );
		\add_action( 'wp_mail_result_extra', array( self::class, 'extra' ), 2 );
	}

	private static function get_current_profile() {
		if ( self::$current_profile ) {
			return array(
				'id'   => self::$current_profile['id'],
				'name' => self::$current_profile['name'],
			);
		}

		return array();
	}

	public static function wp_mail_filter( $atts ) {
		global $wpdb;

		$emails_str = is_array( $atts['to'] ) ? implode( ',', $atts['to'] ) : $atts['to'];
		$subject    = trim( (string) $atts['subject'] );
		$message    = trim( (string) $atts['message'] );

		$searchable = wp_strip_all_tags( $emails_str . '|' . $subject . '|' . $message );
		$searchable = str_replace( "\r\n", ' ', $searchable );
		$searchable = str_replace( "\n", ' ', $searchable );
		$searchable = preg_replace( '/\s{1,}/', ' ', $searchable );
		$searchable = strtolower( $searchable );
		$searchable = trim( $searchable );
		$profile    = array();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			self::get_table_name(),
			array(
				'email_data' => serialize(
					array(
						'start' => microtime( true ),
						'data'  => $atts,
					)
				),

				'status'     => 'unknown',
				'searchable' => $searchable,
			)
		);

		self::$current_id = $wpdb->insert_id;

		return $atts;
	}

	public static function get( $id ) {
		global $wpdb;

		$table_name = self::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$item = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM %i WHERE ID = %d', $table_name, $id ),
			ARRAY_A
		);

		if ( $item && isset( $item['email_data'] ) ) {
			$item['email_data'] = unserialize( $item['email_data'] );
		}

		return $item;
	}

	private static function update( $id, $data ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->update(
			self::get_table_name(),
			$data,
			array( 'ID' => $id )
		);
	}

	public static function failed( $error ) {
		$id  = self::$current_id;
		$log = self::get( $id );

		if ( $log ) {
			$log['status'] = 'failed';

			if ( ! isset( $log['email_data']['failed_reasons'] ) ) {
				$log['email_data']['failed_reasons'] = array();
			}

			$log['email_data']['failed_reasons'][] = array(
				'time'    => microtime( true ),
				'message' => $error->get_error_message(),
				'profile' => self::get_current_profile(),
			);

			unset( $log['created_at'] );
			unset( $log['updated_at'] );

			$log['email_data'] = serialize( $log['email_data'] );

			self::update( $id, $log );
		}
	}

	public static function succeeded() {
		$id  = self::$current_id;
		$log = self::get( $id );

		if ( $log ) {
			$log['status']                     = 'sent';
			$log['email_data']['sent_at']      = microtime( true );
			$log['email_data']['sent_profile'] = self::get_current_profile();
			$log['email_data']                 = serialize( $log['email_data'] );

			unset( $log['created_at'] );
			unset( $log['updated_at'] );

			self::update( $id, $log );
		}
	}

	public static function extra( $data ) {
		if ( empty( $data ) ) {
			return;
		}

		$id  = self::$current_id;
		$log = self::get( $id );

		if ( $log ) {
			if ( ! isset( $log['email_data']['extra'] ) ) {
				$log['email_data']['extra'] = array();
			}

			$log['email_data']['extra'] = array_merge( $log['email_data']['extra'], $data );
			$log['email_data']          = serialize( $log['email_data'] );

			self::update( $id, $log );
		}
	}

	private static function time_ago( $current_timestamp, $timestamp ) {
		$diff = $current_timestamp - $timestamp;

		$if_s = function ( $v ) {
			return 1 === $v ? '' : 's';
		};

		if ( $diff < 60 ) {
			return $diff . ' sec ago';
		} elseif ( $diff < 3600 ) {
			$minutes = floor( $diff / 60 );
			return $minutes . ' min' . $if_s( $minutes ) . ' ago';
		} elseif ( $diff < 86400 ) {
			$hours = floor( $diff / 3600 );
			return $hours . ' hour' . $if_s( $hours ) . ' ago';
		} elseif ( $diff < 604800 ) {
			$days = floor( $diff / 86400 );
			return $days . ' day' . $if_s( $days ) . ' ago';
		} elseif ( $diff < 2419200 ) {
			$weeks = floor( $diff / 604800 );
			return $weeks . ' week' . $if_s( $weeks ) . ' ago';
		} elseif ( $diff < 29030400 ) {
			$months = floor( $diff / 2419200 );
			return $months . ' month' . $if_s( $months ) . ' ago';
		} else {
			return gmdate( 'F j, Y', $timestamp );
		}
	}

	private static function normalize_multiple_recipients( $recipients ) {
		if ( is_array( $recipients ) ) {
			return implode( ', ', $recipients );
		}

		return $recipients;
	}

	private static function make_d_p_name( $name ) {
		$maxlen = 20;

		if ( strlen( $name ) > $maxlen ) {
			return substr( $name, 0, $maxlen - 2 ) . '..';
		}

		return $name;
	}

	public static function format_list_item( $item ) {
		$item['email_data'] = unserialize( $item['email_data'] );

		$failed_reasons  = isset( $item['email_data']['failed_reasons'] ) ? $item['email_data']['failed_reasons'] : array();
		$timeline        = array();
		$start_timestamp = $item['email_data']['start'];
		$took_total      = 0;

		foreach ( $failed_reasons as $failed_reason ) {
			$timeline[] = array(
				'message'  => $failed_reason['message'],
				'p_name'   => '*disabled' === $failed_reason['profile']['id'] ? '' : $failed_reason['profile']['name'],
				'd_p_name' => self::make_d_p_name( $failed_reason['profile']['name'] ),
				'took'     => round( $failed_reason['time'] - $start_timestamp, 2 ) . 's',
				'success'  => false,
			);

			$took_total     += round( $failed_reason['time'] - $start_timestamp, 2 );
			$start_timestamp = $failed_reason['time'];
		}

		if ( isset( $item['email_data']['sent_profile'] ) ) {
			$timeline[] = array(
				'message'  => 'Sent',
				'p_name'   => $item['email_data']['sent_profile']['name'],
				'd_p_name' => self::make_d_p_name( $item['email_data']['sent_profile']['name'] ),
				'took'     => round( $item['email_data']['sent_at'] - $start_timestamp, 2 ) . 's',
				'success'  => true,
			);

			$took_total += round( $item['email_data']['sent_at'] - $start_timestamp, 2 );
		}

		$cc  = '';
		$bcc = '';

		$headers = isset( $item['email_data']['data']['headers'] ) ? $item['email_data']['data']['headers'] : array();
		$headers = is_array( $headers ) ? $headers : array();

		foreach ( $headers as $header ) {
			if ( strpos( $header, 'Cc:' ) === 0 ) {
				$cc = substr( $header, 4 );
			}

			if ( strpos( $header, 'Bcc:' ) === 0 ) {
				$bcc = substr( $header, 4 );
			}
		}

		$result = array(
			'_id'      => $item['ID'],
			'status'   => $item['status'],
			'hstatus'  => ucfirst( $item['status'] ),
			'htime'    => gmdate( "d M, Y \a\\t H:i:s", strtotime( $item['created_at'] ) ),
			'time_ago' => self::time_ago( strtotime( $item['ts'] ), strtotime( $item['created_at'] ) ),
			'timeline' => $timeline,
			'data'     => array(
				'to'      => self::normalize_multiple_recipients( $item['email_data']['data']['to'] ),
				'cc'      => $cc,
				'bcc'     => $bcc,
				'subject' => $item['email_data']['data']['subject'],
			),
		);

		$result['took'] = $took_total . 's';

		return $result;
	}

	public static function get_logs( $page = 1, $keyword = '' ) {
		global $wpdb;

		if ( ! is_numeric( $page ) ) {
			$page = 1;
		}

		$keyword    = is_string( $keyword ) ? $keyword : '';
		$keyword    = trim( strtolower( $keyword ) );
		$per_page   = 40;
		$table_name = self::get_table_name();

		$total = $keyword
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			? (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i WHERE searchable LIKE %s',
					$table_name,
					'%' . $wpdb->esc_like( $keyword ) . '%',
				)
			)
			: (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $table_name ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$total_pages = ceil( $total / $per_page );
		$page        = $page < 1 ? 1 : $page;
		$page        = $page > $total_pages ? $total_pages : $page;
		$start       = ( $page - 1 ) * $per_page;
		$start       = $start < 0 ? 0 : $start;

		$items = $keyword
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			? $wpdb->get_results(
				$wpdb->prepare(
					'SELECT *, CURRENT_TIMESTAMP as ts FROM %i WHERE searchable LIKE %s ORDER BY ID DESC LIMIT %d, %d',
					$table_name,
					'%' . $wpdb->esc_like( $keyword ) . '%',
					$start,
					$per_page
				),
				ARRAY_A
			)
			: $wpdb->get_results(
				$wpdb->prepare(
					'SELECT *, CURRENT_TIMESTAMP as ts FROM %i ORDER BY ID DESC LIMIT %d, %d',
					$table_name,
					$start,
					$per_page
				),
				ARRAY_A
			);

		$items      = array_map( array( self::class, 'format_list_item' ), $items );
		$from       = $start + 1;
		$count      = $from + count( $items ) - 1;
		$of         = $from === $count ? $from : $from . ' to ' . $count;
		$of         = ( 0 === $total ) ? '0' : $of;
		$page_label = $of . ' of ' . $total;

		return array( 200, compact( 'total', 'total_pages', 'page', 'items', 'page_label' ) );
	}

	private static function is_html( $message, $headers ) {
		$result = (bool) preg_match( '/<\s?[^\>]*\/?\s?>/i', $message );

		foreach ( (array) $headers as $header ) {
			$header = strtolower( $header );
			$header = trim( $header );
			$header = preg_replace( '/\s{1,}/', ' ', $header );

			if ( 0 === strpos( $header, 'content-type: text/plain' ) ) {
				return false;
			}
		}

		return $result;
	}

	public static function delete( $ids, $page = null, $keyword = '' ) {
		global $wpdb;

		if ( in_array( '*all', $ids, true ) ) {
			$ids = array( '*all' );
			$sql = 'DELETE FROM %i';
		} else {
			$ids = array_filter( $ids, 'is_numeric' );
			$sql = 'DELETE FROM %i WHERE ID IN(' . implode( ',', $ids ) . ')';
		}

		// Although WordPress.DB.PreparedSQL.NotPrepared is used, it is prepared. It's just wpcs couldn't detect.
		$wpdb->query( $wpdb->prepare( $sql, self::get_table_name() ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( $page || $keyword ) {
			$page = is_numeric( $page ) ? $page : 1;
			return self::get_logs( $page, $keyword );
		}

		return array( 200, $ids );
	}

	private static function get_string_value( &$arr, $key ) {
		$value = isset( $arr[ $key ] ) ? $arr[ $key ] : '';
		$value = is_string( $value ) ? $value : '';
		$value = trim( $value );

		return $value;
	}

	public static function resend_email( $data ) {
		$id     = isset( $data['_id'] ) ? $data['_id'] : 'x';
		$id     = is_numeric( $id ) ? $id : false;
		$errors = array();

		if ( false === $id ) {
			return array( 404, 'Email not found' );
		}

		$to = Send::normalize_recipients( self::get_string_value( $data, 'to' ) );

		if ( $to->empty ) {
			$errors['to'] = 'Required';
		} elseif ( $to->invalid ) {
			$errors['to'] = sprintf( 'Invalid "%s"', $to->invalid );
		}

		$cc = Send::normalize_recipients( self::get_string_value( $data, 'cc' ) );

		if ( ! $cc->empty && $cc->invalid ) {
			$errors['cc'] = sprintf( 'Invalid "%s"', $cc->invalid );
		}

		$bcc = Send::normalize_recipients( self::get_string_value( $data, 'bcc' ) );

		if ( ! $bcc->empty && $bcc->invalid ) {
			$errors['bcc'] = sprintf( 'Invalid "%s"', $bcc->invalid );
		}

		if ( 0 !== count( $errors ) ) {
			return array( 422, $errors );
		}

		$item = self::get( $id );

		if ( ! is_array( $item ) || ! isset( $item['email_data']['data'] ) ) {
			return array( 404, 'Email not found' );
		}

		$item    = $item['email_data']['data'];
		$subject = self::get_string_value( $data, 'subject' );
		$headers = $item['headers'];

		foreach ( $headers as $i => $header ) {
			$header = strtolower( $header );

			if ( 0 === strpos( $header, 'cc:' ) || 0 === strpos( $header, 'bcc:' ) ) {
				unset( $headers[ $i ] );
			}
		}

		if ( ! $cc->empty ) {
			$headers[] = sprintf( 'Cc: %s', implode( ', ', $cc->emails ) );
		}

		if ( ! $bcc->empty ) {
			$headers[] = sprintf( 'Bcc: %s', implode( ', ', $bcc->emails ) );
		}

		$item['subject'] = $subject;
		$item['to']      = $to->emails;
		$item['headers'] = $headers;

		$result = call_user_func_array( 'wp_mail', $item );

		return array( 200, $result );
	}
}
