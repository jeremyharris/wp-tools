<?php

require dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'libs' . DIRECTORY_SEPARATOR . 'Shell.php';
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'WPToolsDatabaseTestCase.php';

class MoveTest extends WPToolsDatabaseTestCase {

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
