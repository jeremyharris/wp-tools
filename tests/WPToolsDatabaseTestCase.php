<?php

require 'PHPUnit' . DIRECTORY_SEPARATOR . 'Extensions' . DIRECTORY_SEPARATOR . 'Database'  . DIRECTORY_SEPARATOR . 'TestCase.php';

class ShellProxy extends Shell {
	public function getConnection() {
		return parent::getConnection();
	}
	public function getBlogs() {
		return parent::getBlogs();
	}
	public function _quit() {
		return parent::_quit();
	}
}

/**
 * Test case for when database interaction is needed. Automatically sets up
 * fixture tables and drops them on tear down.
 */
class WPToolsDatabaseTestCase extends PHPUnit_Extensions_Database_TestCase {
	
/**
 * WP install suffix, for getting the right `wp-config.php`
 * 
 * @var string
 */
	protected $install = 'multi';
	
/**
 * Stored table prefix, since we can't reload the WP config more than once
 * per test case
 * 
 * @var string
 */
	private static $table_prefix = null;
	
/**
 * List of XML-based fixtures
 * 
 * @var array
 */
	private $fixtures = array(
		'prefix_site',
		'prefix_blogs',
		'prefix_posts',
		'prefix_2_posts',
		'prefix_3_posts',
		'prefix_postmeta',
		'prefix_2_postmeta',
		'prefix_3_postmeta',
		'prefix_options',
		'prefix_2_options',
		'prefix_3_options',
		'single_options',
	);
	
/**
 * PHPUnit DB connection
 * 
 * @var PHPUnit_Extensions_Database_DB_IDatabaseConnection 
 */
	private $conn = null;

/**
 * Set up tables and Shell mock
 */
	public function setUp() {
		$conn = $this->getConnection();
		$pdo = $conn->getConnection();
		
		// set up tables
		$fixtureDataSet = $this->getDataSet($this->fixtures);
		foreach ($fixtureDataSet->getTableNames() as $table) {
			// drop table
			$pdo->exec("DROP TABLE IF EXISTS `$table`;");
			// recreate table
			$meta = $fixtureDataSet->getTableMetaData($table);
			$create = "CREATE TABLE IF NOT EXISTS `$table` ";
			$cols = array();
			foreach ($meta->getColumns() as $col) {
				$cols[] = "`$col` VARCHAR(200)";
			}
			$create .= '('.implode(',', $cols).');';
			$pdo->exec($create);
		}
		
		// build shell
		$arguments = array(
			 'scriptname',
			 'help',
			 '-w',
			 dirname(__FILE__) . DIRECTORY_SEPARATOR . "wordpress_$this->install/"
		);
		$this->Shell = $this->getMock('ShellProxy', array('in', 'out', 'error', 'getConnection', '_quit'), array($arguments));
		$this->Shell
			->expects($this->any())
			->method('getConnection')
			->will($this->returnValue($this->getConnection()->getConnection()));
		
		// we need this var to persist since it's set in `Shell::loadWP` which
		// can't be again
		if (self::$table_prefix === null) {
			self::$table_prefix = $this->Shell->table_prefix;
		} else {
			$this->Shell->table_prefix = self::$table_prefix;
		}
		
		parent::setUp();
	}

/**
 * Clean up database
 */
	public function tearDown() {
		$allTables = $this->getDataSet($this->fixtures)->getTableNames();
		foreach ($allTables as $table) {
			// drop table
			$conn = $this->getConnection();
			$pdo = $conn->getConnection();
			$pdo->exec("DROP TABLE IF EXISTS `$table`;");
		}
	}

/**
 * Returns a connection to the test database
 * 
 * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
 */
	public function getConnection() {
		if ($this->conn === null) {
			$pdo = new PDO('mysql:host=localhost;dbname=test', 'root', '');
			$this->conn = $this->createDefaultDBConnection($pdo, 'test');
		}
		return $this->conn;
	}

/**
 * Returns a composite dataset of all fixtures passed to `$fixtures`
 * 
 * @param array $fixtures Array of fixtures to add to the dataset
 * @return PHPUnit_Extensions_Database_DataSet_IDataSet
 */
	public function getDataSet($fixtures = array('prefix_site')) {
		$compositeDs = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet(array());
		$fixturePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'database';
		
		foreach ($fixtures as $fixture) {
			$path =  $fixturePath . DIRECTORY_SEPARATOR . "$fixture.xml";
			$ds = $this->createMySQLXMLDataSet($path);
			$compositeDs->addDataSet($ds);
		}
		return $compositeDs;
	}

/**
 * Loads all tables and rows from a dataset into the database
 * 
 * @param PHPUnit_Extensions_Database_DataSet_IDataSet $dataSet Dataset to load
 */
	public function loadDataSet($dataSet) {
		// set the new dataset
		$this->getDatabaseTester()->setDataSet($dataSet);
		// call setUp which adds the rows
		$this->getDatabaseTester()->onSetUp();
	}
	
}