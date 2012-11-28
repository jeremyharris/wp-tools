<?php

require '..' . DIRECTORY_SEPARATOR . 'libs' . DIRECTORY_SEPARATOR . 'Shell.php';

class ShellTest extends PHPUnit_Framework_TestCase {
	
	public function testParseArgs() {
		$shell = $this->getMock('Shell', array('out', 'error', 'in', 'command'), array(), '', false);
		$arguments = array(
			'scriptname'
		);
		$results = $shell->parseArgs($arguments);
		$expected = array('help', array(), array());
		$this->assertEquals($expected, $results);
		
		$this->assertFalse($shell->wpPath);
		
		$shell = $this->getMock('Shell', array('out', 'error', 'in', 'command'), array(), '', false);
		$arguments = array(
			'scriptname',
			'command'
		);
		$results = $shell->parseArgs($arguments);
		$expected = array('command', array(), array());
		$this->assertEquals($expected, $results);
		
		$this->assertFalse($shell->wpPath);
		
		$shell = $this->getMock('Shell', array('out', 'error', 'in', 'command'), array(), '', false);
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
		
		$shell = $this->getMock('Shell', array('out', 'error', 'in', 'command'), array(), '', false);
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
		
		$shell = $this->getMock('Shell', array('out', 'error', 'in', 'command'), array(), '', false);
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
		
		$shell = $this->getMock('Shell', array('out', 'error', 'in', 'command'), array(), '', false);
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
	
}