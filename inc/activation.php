<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange

/**
 * Create table for main data storing
 */
$table_name = $wpdb->prefix . 'email_king_wp';

$wpdb->query(
	$wpdb->prepare(
		'CREATE TABLE IF NOT EXISTS %i (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `_key` varchar(255) DEFAULT NULL,
      `_data` text DEFAULT NULL,
      `_created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `_updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`),
      UNIQUE KEY `unique_key` (`_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;',
		$table_name,
	)
);

/**
 * Create table for emails logs
 */
$table_name = $wpdb->prefix . 'ek_email_logs';

$wpdb->query(
	$wpdb->prepare(
		'CREATE TABLE IF NOT EXISTS %i (
      `ID` int(11) NOT NULL AUTO_INCREMENT, 
      `email_data` text DEFAULT NULL, 
      `searchable` text NOT NULL, 
      `status` varchar(22) NOT NULL, 
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(), 
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(), 
      PRIMARY KEY (`ID`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;',
		$table_name
	)
);

// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:enable WordPress.DB.DirectDatabaseQuery.SchemaChange