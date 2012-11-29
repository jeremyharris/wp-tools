<?php

require '..' . DIRECTORY_SEPARATOR . 'libs' . DIRECTORY_SEPARATOR . 'Shell.php';

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
	
}