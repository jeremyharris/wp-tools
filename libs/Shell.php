<?php
/**
 * Simple class for interacting with the shell
 */
class Shell {

/**
 * File descriptor for stdin
 * 
 * @var resource 
 */
	protected $stdin;
	
/**
 * File descriptor for stdout
 * 
 * @var resource 
 */
	protected $stdout;

/**
 * File descriptor for stderr
 * 
 * @var resource 
 */
	protected $stderr;
	
/**
 * Shell arguments passed
 * 
 * @var array 
 */
	protected $args = array();
	
/**
 * Path to WordPress installation, without trailing DS
 * 
 * @var string
 */
	public $wpPath = false;
	
/**
 * Table prefix global var
 * 
 * @var string
 */
	public $table_prefix;
	
/**
 * PDO connection
 * 
 * @var PDO
 */
	public $connection = null;
	
/**
 * Whitelist of commands that can be run
 * 
 * @var array 
 */
	protected $commands = array(
		'help',
		'move',
		'clean-orphans'
	);
	
/**
 * Creates file descriptors and outputs welcome message
 * 
 * @param array $arguments Shell args (usually from `$argv`)
 */
	public function __construct($arguments = array()) {
		$this->stdin = fopen('php://stdin', 'w');
		$this->stdout = fopen('php://stdout', 'w');
		$this->stderr = fopen('php://stderr', 'w');
		
		list($method, $this->args, $passedArgs) = $this->parseArgs($arguments);
		
		$this->loadWP();
		
		$this->welcome();
		
		$method = $this->camelcase($method);
		
		call_user_func_array(array($this, $method), $passedArgs);
	}

/**
 * Parses arguments sent by the CLI in a very simplistic way. The first argument
 * is considered the command and is in the returned array's first index. 
 * Remaining arguments are returned as key-value pairs in the second index. 
 * Lastly, floating arguments are returned in the third index to be passed as
 * parameters to the method.
 * 
 * Example: pass `command -r Something Nothing -v Value
 * Yields: 
 * ```
 * array(
 *   'command',
 *   array(
 *     '-r' => 'Something',
 *     '-v' => 'Value'
 *   ),
 *   array(
 *     'Nothing'
 *   )
 * )
 * ```
 * 
 * @param array $arguments `$argv` formatted arguments
 * @return array
 */
	public function parseArgs($arguments) {
		if (count($arguments) < 2) {
			return array('help', array(), array());
		}
		unset($arguments[0]);
		$method = $arguments[1];
		unset($arguments[1]);
		
		if (!in_array($method, $this->commands)) {
			$method = 'help';
		}
		
		// look for keyed arguments (-s something)
		$parsedArgs = array();
		foreach ($arguments as $key => $arg) {
			if (strpos($arg, '-') === 0) {
				$parsedArgs[$arg] = $arguments[$key+1];
				unset($arguments[$key+1]);
				unset($arguments[$key]);
			}
		}
		
		if (isset($parsedArgs['-w'])) {
			$this->wpPath = rtrim($parsedArgs['-w'], '\\/');
			unset($parsedArgs['-w']);
		}
		
		return array(
			$method,
			$parsedArgs,
			(array)array_values($arguments)
		);
	}
	
/**
 * Outputs welcome message
 */
	protected function welcome() {
		$this->out("\n");
		$this->out("WP Tools: A collection of tools for managing a WordPress database.");
		$this->out("** All changes are made directly to the database and therefore permanent!");
		$this->out("** Make sure to back it up before attempting to move it.\n");
		$this->out('hr');
	}
	
/**
 * Outputs help message
 */	
	protected function help() {
		$this->out("\n", false);
		$this->out("Commands:");
		$this->out("  - `help`: Display this help message");
		$this->out("  - `move`: Move WP database entries to a new domain");
		$this->out("  - `clean-orphans`: Remove old, unused metadata");
	}

/**
 * Accepts a user response from stdin
 * 
 * @param string $prompt The question to prompt
 * @return string User response 
 */	
	public function in($prompt = '?') {
		$this->out("$prompt: ", false);
		$result = fgets($this->stdin);
		if ($result === false) {
			exit;
		}
		$result = trim($result);
		if (empty($result)) {
			$this->out("Invalid response, please try again.");
			return in($prompt);
		}
		return $result;
	}

/**
 * Sends a message to stdout. Sending 'hr' creates a horizontal rule.
 * 
 * @param string $msg The message
 * @param boolean $newline Append a new line
 */
	public function out($msg = '', $newline = true) {
		$msg = "$msg";
		if ($msg == 'hr') {
			$msg = str_repeat('-', 80);
		} elseif ($newline) {
			$msg .= "\n";
		}
		fwrite($this->stdout, $msg);
	}

/**
 * Sends an error to stderr
 * 
 * @param string $msg Error to send
 */
	public function error($msg = '') {
		fwrite($this->stderr, $msg."\n");
	}

/**
 * Error handler to output CLI-friendly errors
 * 
 * @param int $errorno
 * @param string $errormsg
 */
	public function php_error($errorno, $errormsg) {
		$this->error("PHP ERROR: $errormsg");
	}

/**
 * Moves DB from one domain to another
 */
	public function move() {
		$connection = $this->getConnection();

		$this->out("\n", false);
		$this->out("Please type the domain you wish to move the following domain(s) to:");
		$this->out("  s: skip current blog");
		$this->out("  q: quit shell\n");
		
		$blogs = $this->getBlogs();

		foreach ($blogs as $id => $domain) {
			$prefix = $this->table_prefix;
			if ($id != BLOG_ID_CURRENT_SITE) {
				$prefix = $this->table_prefix.$id.'_';
			}
			$new = $this->in("($id) $domain");
			if ($new == 'q') {
				return;
			}
			if ($new == 's') {
				$this->out("Skipped moving $domain\n");
				continue;
			}

			try {
				$newHost = stripos($new, 'http') !== false ? parse_url($new, PHP_URL_HOST) : $new;
				$oldHost = stripos($domain, 'http') !== false ? parse_url($domain, PHP_URL_HOST) : $domain;
				$values = array(':new' => $newHost, ':old' => $oldHost);
				
				$connection->exec("BEGIN");
				
				$update = $connection->prepare("UPDATE `{$this->table_prefix}blogs` SET `domain` = :new WHERE `domain` = :old");
				$blogUpdate = $update->execute($values);
				$update = $connection->prepare("UPDATE `{$prefix}options` SET `option_value` = REPLACE(`option_value`, :old, :new) WHERE `option_name` = 'siteurl';");
				$siteurlUpdate = $update->execute($values);
				$update = $connection->prepare("UPDATE `{$prefix}options` SET `option_value` = REPLACE(`option_value`, :old, :new) WHERE `option_name` = 'home';");
				$homeUpdate = $update->execute($values);
				$update = $connection->prepare("UPDATE `{$prefix}posts` SET `guid` = REPLACE(`guid`, :old, :new);");
				$postsUpdate = $update->execute($values);

				if (!$blogUpdate || !$siteurlUpdate || !$homeUpdate || !$postsUpdate) {
					$connection->exec("ROLLBACK");
					$this->out("Error moving $domain to $new\n");
					continue;
				}
				$this->out("Moved $domain to $new\n");
				$connection->exec("COMMIT");
			} catch (PDOException $e) {
				$connection->exec("ROLLBACK");
				$this->error($e->getMessage());
				continue;
			}
		}

		$this->out("Finished.\n");
		$this->out("Make sure to change the DOMAIN_CURRENT_SITE constant in `wp-config.php`");
	}

/**
 * Removes post meta that doesn't belong to an existing post
 */	
	public function cleanOrphans() {
		$connection = $this->getConnection();
		$blogs = $this->getBlogs();

		foreach ($blogs as $id => $domain) {
			$prefix = $this->table_prefix;
			if ($id != BLOG_ID_CURRENT_SITE || $id === 0) {
				$prefix = $this->table_prefix.$id.'_';
			}
			
			// get orphaned metadata
			$query = $connection->prepare("
				SELECT `meta_id`
				FROM `{$prefix}postmeta` as pm
				LEFT JOIN `{$prefix}posts` as p ON (`pm`.`post_id` = `p`.`ID`)
				WHERE `p`.`ID` IS NULL;
			");
			try {
				$query->execute();
				$results = $query->fetchAll(PDO::FETCH_COLUMN);
				$count = count($results);
				
				if (empty($results)) {
					$this->out("No orphaned meta records found for $domain");
					continue;
				}
				
				$deleteQuery = $connection->prepare("DELETE FROM `{$prefix}postmeta` WHERE `meta_id` IN (".implode(',', $results).")");
				$deleted = $deleteQuery->execute();
				
				if ($deleted) {
					$this->out("$count orphaned meta record(s) removed for $domain");
				} else {
					$this->out("Could not remove orphaned meta records for $domain");
				}
			} catch (PDOException $e) {
				$this->error($e->getMessage());
				continue;
			}
		}
	}

/**
 * Loads WordPress config file. If it can't be found, the help message is
 * displayed and the shell exits.
 */
	protected function loadWP() {
		if (defined('ABSPATH')) {
			// we've already loaded a config
			return;
		}
		
		$config = $this->wpPath . DIRECTORY_SEPARATOR . 'wp-config.php';
		if (!is_dir($this->wpPath) || !file_exists($config)) {
			$this->out("Please pass the location of your WordPress install as the `-w` argument.\n");
			$this->out("  $ wp-tools <command> -w /path/to/wordpress");
			$this->help();
			exit();
		}
		
		// don't load the WP environment
		define('ABSPATH', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);

		require $config;
		
		$this->table_prefix = $table_prefix;
		
		set_error_handler(array($this, 'php_error'), E_ALL);
	}

/**
 * Gets a connection to the WP database and returns it
 * 
 * @return PDO
 */
	protected function getConnection() {
		if ($this->connection !== null) {
			return $this->connection;
		}
		try {
			$this->connection = new PDO(
				'mysql:host='.DB_HOST.';dbname='.DB_NAME,
				DB_USER,
				DB_PASSWORD,
				array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
			);
			return $this->connection;
		} catch (PDOException $e) {
			$this->error($e->getMessage());
			exit();
		}
	}

/**
 * Takes a `dashed-word` and returns it as a `camelcaseWord`
 * 
 * @param string $out Dashed word
 * @return string camelcaseWord
 */
	protected function camelcase($out = '') {
		if (stripos($out, '-') !== false) {
			$words = explode('-', $out);
			$out = array_shift($words);
			foreach ($words as $word) {
				$out .= ucfirst($word);
			}
		}
		return $out;
	}

/**
 * Gets all blogs on this installation and returns them as a key-value pair,
 * where the blog ID is the key and the value is the domain.
 * 
 * @return array
 */
	protected function getBlogs() {
		$connection = $this->getConnection();
		
		if (defined('MULTISITE') && MULTISITE === true) {
			$query = $connection->prepare("SELECT `blog_id`, `domain` FROM `{$this->table_prefix}blogs` WHERE `deleted` = 0;");
			try {
				$query->execute();
			} catch (PDOException $e) {
				$this->error($e->getMessage());
				exit();
			}
			$results = $query->fetchAll();
			$blogs = array();
			foreach ($results as $row) {
				$blogs[$row['blog_id']] = $row['domain'];
			}
		} else {
			$query = $connection->prepare("SELECT `option_value` FROM `{$this->table_prefix}options` WHERE `option_name` = 'siteurl';");
			try {
				$query->execute();
			} catch (PDOException $e) {
				$this->error($e->getMessage());
				exit();
			}
			$blogs = $query->fetchAll(PDO::FETCH_COLUMN);
			$blogs[0] = preg_replace('/^https?:\/\//', '', $blogs[0]);
		}
		return $blogs;
	}
}
