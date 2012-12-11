<?php

require dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'libs' . DIRECTORY_SEPARATOR . 'Shell.php';
require 'PHPUnit' . DIRECTORY_SEPARATOR . 'Extensions' . DIRECTORY_SEPARATOR . 'Database'  . DIRECTORY_SEPARATOR . 'TestCase.php';

class CommandTestShell extends Shell {
	public function getConnection() {
		return parent::getConnection();
	}
	public function _quit() {
		return parent::_quit();
	}
}

class CommandTest extends PHPUnit_Extensions_Database_TestCase {

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
		'prefix_options',
		'prefix_2_options',
		'prefix_3_options',
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
			 dirname(__FILE__) . DIRECTORY_SEPARATOR . 'wordpress_multi/'
		);
		$this->Shell = $this->getMock('CommandTestShell', array('in', 'out', 'error', 'getConnection', '_quit'), array($arguments));
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
	
	public function testQuit() {
		$ds = $this->getDataSet(array(
			'prefix_site',
			'prefix_blogs',
			'prefix_posts',
			'prefix_2_posts',
			'prefix_2_options'
		));
		$this->loadDataSet($ds);
		
		// answer prompts (immediately quit)
		$this->Shell
			->expects($this->any())
			->method('in')
			->will($this->onConsecutiveCalls('q'));
		
		$this->Shell->move();
		
		$expected = array(
			'http://wordpress.local/?p=1',
			'http://wordpress.local/?page_id=2',
			'http://wordpress.local/?p=3',
			'http://wordpress.local/?p=5'
		);
		$query = $this->Shell->getConnection()->query('SELECT guid FROM prefix_posts;');
		$results = $query->fetchAll(PDO::FETCH_COLUMN);
		$this->assertEquals($expected, $results);
		
		$expected = array(
			'http://site2.wordpress.local/?p=1',
			'http://site2.wordpress.local/?page_id=2'
		);
		$query = $this->Shell->getConnection()->query('SELECT guid FROM prefix_2_posts;');
		$results = $query->fetchAll(PDO::FETCH_COLUMN);
		$this->assertEquals($expected, $results);
	}
	
	public function testScheme() {
		$ds = $this->getDataSet(array(
			'prefix_site',
			'prefix_blogs',
			'prefix_posts'
		));
		$this->loadDataSet($ds);
		
		// answer prompts for this test method
		$this->Shell
			->expects($this->any())
			->method('in')
			->will($this->onConsecutiveCalls(
				'https://secure.example.com',
				'q',
				'securer.example.com',
				'q'
			));
		
		$this->Shell->move();
		
		$expected = array(
			'http://secure.example.com/?p=1',
			'http://secure.example.com/?page_id=2',
			'http://secure.example.com/?p=3',
			'http://secure.example.com/?p=5'
		);
		$query = $this->Shell->getConnection()->query('SELECT guid FROM prefix_posts;');
		$results = $query->fetchAll(PDO::FETCH_COLUMN);
		$this->assertEquals($expected, $results);
		
		$this->Shell->move();
		
		$expected = array(
			'http://securer.example.com/?p=1',
			'http://securer.example.com/?page_id=2',
			'http://securer.example.com/?p=3',
			'http://securer.example.com/?p=5'
		);
		$query = $this->Shell->getConnection()->query('SELECT guid FROM prefix_posts;');
		$results = $query->fetchAll(PDO::FETCH_COLUMN);
		$this->assertEquals($expected, $results);
	}
	
	public function testMoveAndSkip() {
		$ds = $this->getDataSet(array(
			'prefix_site',
			'prefix_blogs',
			'prefix_posts',
			'prefix_2_posts',
			'prefix_2_options',
			'prefix_3_posts',
			'prefix_3_options'
		));
		$this->loadDataSet($ds);
		
		// answer prompts (skip first, rename next two)
		$this->Shell
			->expects($this->any())
			->method('in')
			->will($this->onConsecutiveCalls('s', 'http://sub1.example.org', 'sub2.example.org'));
		
		$this->Shell->move();
		
		$expected = array(
			'http://wordpress.local/?p=1',
			'http://wordpress.local/?page_id=2',
			'http://wordpress.local/?p=3',
			'http://wordpress.local/?p=5'
		);
		$query = $this->Shell->getConnection()->query('SELECT guid FROM prefix_posts;');
		$results = $query->fetchAll(PDO::FETCH_COLUMN);
		$this->assertEquals($expected, $results);
		
		$expected = array(
			'http://sub1.example.org/?p=1',
			'http://sub1.example.org/?page_id=2'
		);
		$query = $this->Shell->getConnection()->query('SELECT guid FROM prefix_2_posts;');
		$results = $query->fetchAll(PDO::FETCH_COLUMN);
		$this->assertEquals($expected, $results);
		
		$expected = array(
			'http://sub2.example.org/?p=1',
			'http://sub2.example.org/?page_id=2'
		);
		$query = $this->Shell->getConnection()->query('SELECT guid FROM prefix_3_posts;');
		$results = $query->fetchAll(PDO::FETCH_COLUMN);
		$this->assertEquals($expected, $results);
	}
	
	function testTransactionFail() {
		$ds = $this->getDataSet(array(
			'prefix_site',
			'prefix_blogs'
		));
		$this->loadDataSet($ds);
		$this->Shell->getConnection()->exec('DROP TABLE `prefix_posts`;');
		
		$this->Shell
			->expects($this->any())
			->method('in')
			->will($this->onConsecutiveCalls('http://sub1.example.org', 'q'));
		
		// this fails because prefix_posts is missing and the db update on it will fail
		$this->Shell->move();
		
		$expected = array(
			'wordpress.local',
			'site2.wordpress.local',
			'site3.wordpress.local'
		);
		$query = $this->Shell->getConnection()->query('SELECT domain FROM prefix_blogs;');
		$results = $query->fetchAll(PDO::FETCH_COLUMN);
		$this->assertEquals($expected, $results);
	}
	
}
