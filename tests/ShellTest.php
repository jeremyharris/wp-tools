<?php

require dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'libs' . DIRECTORY_SEPARATOR . 'Shell.php';

class ShellMock extends Shell {
	
	protected $commands = array(
		'help',
		'command'
	);
	
	public function command($var1, $var2) {
		$this->var1 = $var1;
		$this->var2 = $var2;
		$this->option = $this->args['-o'];
	}
	
	public function loadWP() {
		return parent::loadWP();
	}
	
	public function getConnection() {
		return parent::getConnection();
	}
	
	public function camelcase($word = '') {
		return parent::camelcase($word);
	}
}

class ShellTest extends PHPUnit_Framework_TestCase {
	
	public function testParseArgs() {
		$shell = $this->getMock('ShellMock', array('out', 'error', 'in'), array(), '', false);
		$arguments = array(
			'scriptname'
		);
		$results = $shell->parseArgs($arguments);
		$expected = array('help', array(), array());
		$this->assertEquals($expected, $results);
		
		$this->assertFalse($shell->wpPath);
		
		$shell = $this->getMock('ShellMock', array('out', 'error', 'in'), array(), '', false);
		$arguments = array(
			'scriptname',
			'command'
		);
		$results = $shell->parseArgs($arguments);
		$expected = array('command', array(), array());
		$this->assertEquals($expected, $results);
		
		$this->assertFalse($shell->wpPath);
		
		$shell = $this->getMock('ShellMock',  array('out', 'error', 'in'), array(), '', false);
		$arguments = array(
			'scriptname',
			'command',
			'-w',
			'/path/to/wp'
		);
		$results = $shell->parseArgs($arguments);
		$expected = array('command', array(), array());
		$this->assertEquals($expected, $results);
		
		$results = $shell->wpPath;
		$expected = '/path/to/wp';
		$this->assertEquals($expected, $results);
		
		$shell = $this->getMock('ShellMock', array('out', 'error', 'in'), array(), '', false);
		$arguments = array(
			'scriptname',
			'command',
			'-w',
			'/path/to/wp',
			'-o',
			'option'
		);
		$results = $shell->parseArgs($arguments);
		$expected = array(
			'command', 
			array(
			'-o' => 'option'
			),
			array()
		);
		$this->assertEquals($expected, $results);
		
		$results = $shell->wpPath;
		$expected = '/path/to/wp';
		$this->assertEquals($expected, $results);
		
		$shell = $this->getMock('ShellMock', array('out', 'error', 'in'), array(), '', false);
		$arguments = array(
			'scriptname',
			'command',
			'passed option',
			'-o',
			'option',
			'other passed option'
		);
		$results = $shell->parseArgs($arguments);
		$expected = array(
			'command', 
			array(
				'-o' => 'option'
			),
			array(
				'passed option',
				'other passed option'
			)
		);
		$this->assertEquals($expected, $results);
		
		$this->assertFalse($shell->wpPath);
		
		$shell = $this->getMock('ShellMock', array('out', 'error', 'in'), array(), '', false);
		$arguments = array(
			'scriptname',
			'command',
			'passed option',
			'-o',
			'option',
			'other passed option',
			'-k',
			'keyed',
			'-w',
			'/path/to/wp'
		);
		$results = $shell->parseArgs($arguments);
		$expected = array(
			'command', 
			array(
				'-o' => 'option',
				'-k' => 'keyed'
			),
			array(
				'passed option',
				'other passed option'
			)
		);
		$this->assertEquals($expected, $results);
		
		$results = $shell->wpPath;
		$expected = '/path/to/wp';
		$this->assertEquals($expected, $results);
	}
	
	public function testMissingCommand() {
		$shell = $this->getMock('ShellMock', array('out', 'error', 'in'), array(), '', false);
		$arguments = array(
			'scriptname',
			'nonexistentcommand'
		);
		$results = $shell->parseArgs($arguments);
		$expected = array(
			'help', 
			array(),
			array()
		);
		$this->assertEquals($expected, $results);
	}
	
	public function testCallCommand() {
		$arguments = array(
			'scriptname',
			'command',
			'-o',
			'optionvalue',
			'floating1',
			'floating2'
		);
		$shell = $this->getMock('ShellMock', array('out', 'error', 'in', 'loadWP'), array($arguments));
		
		$results = $shell->var1;
		$expected = 'floating1';
		$this->assertEquals($expected, $results);
		
		$results = $shell->var2;
		$expected = 'floating2';
		$this->assertEquals($expected, $results);
		
		$results = $shell->option;
		$expected = 'optionvalue';
		$this->assertEquals($expected, $results);
	}

	public function testLoadWP() {
		$arguments = array(
			 'scriptname',
			 'command',
			 '-w',
			 dirname(__FILE__) . DIRECTORY_SEPARATOR . 'wordpress_multi/'
		);
		$shell = $this->getMock('ShellMock', array('out', 'error', 'in'), array($arguments));
		
		$results = $shell->wpPath;
		$expected = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'wordpress_multi';
		$this->assertEquals($expected, $results);
		
		$results = $shell->table_prefix;
		$expected = 'prefix_';
		$this->assertEquals($expected, $results);
		
		$this->assertTrue(defined('DB_NAME'));
		$this->assertFalse(defined('WRONG_WP_SETTINGS_LOADED'));
		
		$arguments = array(
			 'scriptname',
			 'command',
			 '-w',
			 dirname(__FILE__) . DIRECTORY_SEPARATOR . 'wordpress_multi\\'
		);
		
		$shell = $this->getMock('ShellMock', array('out', 'error', 'in'), array($arguments));
		
		$results = $shell->wpPath;
		$expected = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'wordpress_multi';
		$this->assertEquals($expected, $results);
	}
	
	public function testGetConnection() {
		$arguments = array(
			 'scriptname',
			 'command',
			 '-w',
			 dirname(__FILE__) . DIRECTORY_SEPARATOR . 'wordpress_multi/'
		);
		$shell = $this->getMock('ShellMock', array('out', 'error', 'in'), array($arguments), '', false);
		
		$this->assertTrue($shell->connection === null);
		$connection = $shell->getConnection();
		$this->assertInstanceOf('PDO', $connection);
		$this->assertInstanceOf('PDO', $shell->connection);
		
		$expected = $shell->connection;
		$result = $shell->getConnection();
		$this->assertSame($expected, $result);
	}
	
	public function testCamelcase() {
		$shell = $this->getMock('ShellMock', array('out', 'error', 'in'), array(), '', false);
		
		$expected = 'someMethod';
		$result = $shell->camelcase('some-method');
		$this->assertEquals($expected, $result);
	}
}
