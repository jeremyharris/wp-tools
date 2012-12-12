<?php

require dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'libs' . DIRECTORY_SEPARATOR . 'Shell.php';
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'WPToolsDatabaseTestCase.php';

class CleanOrphansTest extends WPToolsDatabaseTestCase {

	public function testCleanOrphans() {
		$ds = $this->getDataSet(array(
			'prefix_site',
			'prefix_blogs',
			'prefix_posts',
			'prefix_postmeta',
			'prefix_2_posts',
			'prefix_2_postmeta',
			'prefix_2_options',
			'prefix_3_posts',
			'prefix_3_postmeta',
			'prefix_3_options'
		));
		$this->loadDataSet($ds);
		
		$this->Shell->cleanOrphans();
		
		$expected = array(
			1 => array(
				'Something'
			),
			2 => array(
				'MetaKey'
			)
		);
		$query = $this->Shell->getConnection()->query('SELECT post_id, meta_key FROM prefix_postmeta;');
		$results = $query->fetchAll(PDO::FETCH_COLUMN|PDO::FETCH_GROUP);
		$this->assertEquals($expected, $results);
		
		$expected = array(
			1 => array(
				'some_meta',
				'MetaKey'
			)
		);
		$query = $this->Shell->getConnection()->query('SELECT post_id, meta_key FROM prefix_2_postmeta;');
		$results = $query->fetchAll(PDO::FETCH_COLUMN|PDO::FETCH_GROUP);
		$this->assertEquals($expected, $results);
		
		$expected = array(
			1 => array(
				'template',
				'tagline',
				'more'
			)
		);
		$query = $this->Shell->getConnection()->query('SELECT post_id, meta_key FROM prefix_3_postmeta;');
		$results = $query->fetchAll(PDO::FETCH_COLUMN|PDO::FETCH_GROUP);
		$this->assertEquals($expected, $results);
	}
	
}
