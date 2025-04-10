<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/dash/back/init.php';
require_once __DIR__ . '/smtp/back/init.php';
require_once __DIR__ . '/logs/back/init.php';
require_once __DIR__ . '/send/back/init.php';

$mods = array(
	'dash',
	'smtp',
	'logs',
	'send',
);

foreach ( $mods as $mod ) {
	call_user_func( 'email_king_wp__init_mod_' . $mod );
}
