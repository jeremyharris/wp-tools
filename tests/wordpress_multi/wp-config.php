<?php
/**
 * config file for tests
 */
define('DB_NAME', 'test');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_HOST', '127.0.0.1');
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');

define('BLOG_ID_CURRENT_SITE', 1);

$table_prefix  = 'prefix_';

if (!defined('ABSPATH')) {
	define('ABSPATH', dirname(__FILE__) . '/');
}

require_once(ABSPATH . 'wp-settings.php');