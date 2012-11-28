<?php

$stdin = fopen('php://stdin', 'w');
$stdout = fopen('php://stdout', 'w');
$stderr = fopen('php://stderr', 'w');

out('hr');
out("MOVE WP: A simple tool for moving WP around environments\n");
out("** All changes are made directly to the database and therefore permanent!");
out("** Make sure to back it up before attempting to move it.\n");
out("Depending on the size of your database, these operations could take up to");
out("a few minutes.");
out('hr');

if (!isset($argv[1]) || !file_exists($argv[1]) || is_dir($argv[1])) {
	error("Please pass the location of your `wp-config.php` file as the first argument.\n");
	error("  $ move-wp.php /path/to/wp-config.php");
	exit();
}

// don't load the WP environment
define('ABSPATH', './');

include $argv[1];

$connection = null;
try {
	$connection = new PDO(
		'mysql:host='.DB_HOST.';dbname='.DB_NAME,
		DB_USER,
		DB_PASSWORD,
		array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
	);
} catch (PDOException $e) {
	error($e->getMessage());
	exit();
}

$query = $connection->prepare("SELECT * FROM `{$table_prefix}blogs` WHERE `deleted` = 0;");
try {
	$query->execute();
} catch (PDOException $e) {
	error($e->getMessage());
	exit();
}

out("\n", true);
$scheme = in("Choose a scheme [http|https]");
if ($scheme == 'q') {
	exit();
}

out("\n", true);
out("Please type the domain you wish to move the following domain(s) to,");
out("excluding the scheme:");
out("  s: skip current blog");
out("  q: quit shell\n");

while ($blog = $query->fetch(PDO::FETCH_OBJ)) {
	$prefix = $table_prefix;
	if ($blog->blog_id != BLOG_ID_CURRENT_SITE) {
		$prefix = $table_prefix.$blog->blog_id.'_';
	}
	$new = in("($blog->blog_id) $blog->domain");
	if ($new == 'q') {
		exit();
	}
	if ($new == 's') {
		out("Skipped moving $blog->domain\n");
		continue;
	}
	
	try {
		$connection->exec("BEGIN");
		$update = $connection->prepare("UPDATE `{$table_prefix}blogs` SET `domain` = :new WHERE `domain` = :old");
		$blogUpdate = $update->execute(array(':new' => $new, ':old' => $blog->domain));
		$update = $connection->prepare("UPDATE `{$prefix}options` SET `option_value` = :new WHERE `option_name` = 'siteurl';");
		$siteurlUpdate = $update->execute(array(':new' => "$scheme://$new"));
		$update = $connection->prepare("UPDATE `{$prefix}options` SET `option_value` = :new WHERE `option_name` = 'home';");
		$homeUpdate = $update->execute(array(':new' => "$scheme://$new"));
		$update = $connection->prepare("UPDATE `{$prefix}posts` SET `guid` = REPLACE(`guid`, :old, :new);");
		$postsUpdate = $update->execute(array(':new' => $new, ':old' => $blog->domain));
		
		if (!$blogUpdate || !$siteurlUpdate || !$homeUpdate || !$postsUpdate) {
			$connection->exec("ROLLBACK");
			out("Error moving $blog->domain to $new\n");
			continue;
		}
		out("Moved $blog->domain to $new\n");
		$connection->exec("COMMIT");
	} catch (PDOException $e) {
		error($e->getMessage());
		continue;
	}
}

out("Finished.\n");
out("Make sure to change the DOMAIN_CURRENT_SITE constant in `wp-config.php`");

function in($prompt = '?') {
	global $stdin;
	out("$prompt: ", true);
	$result = fgets($stdin);
	if ($result === false) {
		exit;
	}
	$result = trim($result);
	if (empty($result)) {
		out("Invalid response, please try again.");
		return in($prompt);
	}
	return $result;
}

function out($msg = '', $prompt = false) {
	global $stdout;
	$msg = " $msg";
	if ($msg == ' hr') {
		$msg = str_repeat('-', 80);
	} elseif (!$prompt) {
		$msg .= "\n";
	}
	fwrite($stdout, $msg);
}

function error($msg = '') {
	global $stderr;
	fwrite($stderr, $msg."\n");
}