<?php

require 'libs' . DIRECTORY_SEPARATOR . 'Shell.php';

$shell = new Shell($argv);
$connection = null;
try {
	$connection = new PDO(
		'mysql:host='.DB_HOST.';dbname='.DB_NAME,
		DB_USER,
		DB_PASSWORD,
		array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
	);
} catch (PDOException $e) {
	$shell->error($e->getMessage());
	exit();
}

$query = $connection->prepare("SELECT * FROM `{$table_prefix}blogs` WHERE `deleted` = 0;");
try {
	$query->execute();
} catch (PDOException $e) {
	$shell->error($e->getMessage());
	exit();
}

$shell->out("\n", false);
$scheme = $shell->in("Choose a scheme [http|https]");
if ($scheme == 'q') {
	exit();
}

$shell->out("\n", false);
$shell->out("Please type the domain you wish to move the following domain(s) to,");
$shell->out("excluding the scheme:");
$shell->out("  s: skip current blog");
$shell->out("  q: quit shell\n");

while ($blog = $query->fetch(PDO::FETCH_OBJ)) {
	$prefix = $table_prefix;
	if ($blog->blog_id != BLOG_ID_CURRENT_SITE) {
		$prefix = $table_prefix.$blog->blog_id.'_';
	}
	$new = $shell->in("($blog->blog_id) $blog->domain");
	if ($new == 'q') {
		exit();
	}
	if ($new == 's') {
		$shell->out("Skipped moving $blog->domain\n");
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
			$shell->out("Error moving $blog->domain to $new\n");
			continue;
		}
		$shell->out("Moved $blog->domain to $new\n");
		$connection->exec("COMMIT");
	} catch (PDOException $e) {
		$shell->error($e->getMessage());
		continue;
	}
}

$shell->out("Finished.\n");
$shell->out("Make sure to change the DOMAIN_CURRENT_SITE constant in `wp-config.php`");
