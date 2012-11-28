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
 * Creates file descriptors and outputs welcome message
 * 
 * @param array $arguments Shell args (usually from `$argv`)
 */
	public function __construct($arguments = array()) {
		$this->stdin = fopen('php://stdin', 'w');
		$this->stdout = fopen('php://stdout', 'w');
		$this->stderr = fopen('php://stderr', 'w');
		
		list($method, $this->args) = $this->parseArgs($arguments);
		
		$config = $this->wpPath . DIRECTORY_SEPARATOR . 'wp-config.php';
		if (!is_dir($this->wpPath) || !file_exists($config)) {
			$this->out("Please pass the location of your WordPress install as the `-w` argument.\n");
			$this->out("  $ php $arguments[0] <command> -w /path/to/wordpress");
			exit();
		}
		
		$this->welcome();
	}

/**
 * Parses arguments sent by the CLI in a very simplistic way. Saves any argument
 * with a dash prefix, like `-t somevalue` and stores it as a key-value pair
 * using the dash-prefixed char as the key and the following key as the value.
 * The remaining arguments are saved as indexed values. The first argument is 
 * always considered the command.
 * 
 * Example: pass `command -r Something Nothing -v Value
 * Yields: 
 * ```
 * array(
 *   'command',
 *   array(
 *     '-r' => 'Something',
 *     '-v' => 'Value',
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
			return array('help', array());
		}
		unset($arguments[0]);
		$method = $arguments[1];
		unset($arguments[1]);
		
		if (!method_exists($this, $method)) {
			$arguments[1] = $method;
			$method = 'help';
		}
		
		// look for keyed arguments (-s something)
		$parsedArgs = array();
		foreach ($arguments as $key => $arg) {
			$this->out($arg);
			if (strpos($arg, '-') !== false) {
				$parsedArgs[$arg] = $arguments[$key+1];
				unset($arguments[$key+1]);
				unset($arguments[$key]);
			}
		}
		
		if (isset($parsedArgs['-w'])) {
			$this->wpPath = rtrim($parsedArgs['-w'], '\\/');
			unset($parsedArgs['-w']);
		}
		
		// add standalone, ordered args
		$parsedArgs += array_values($arguments);
		
		return array(
			$method,
			$parsedArgs
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
}