<?php

require dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'libs' . DIRECTORY_SEPARATOR . 'Shell.php';
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'WPToolsDatabaseTestCase.php';

/**
 * Tests database interactions with multisites
 * 
 * @outputBuffering disabled
 */
class ShellDbMultiTest extends WPToolsDatabaseTestCase {
	
	public function testGetBlogs() {
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
		
		$result = $this->Shell->getBlogs();
		$expected = array(
			'1' => 'wordpress.local',
			'2' => 'site2.wordpress.local',
			'3' => 'site3.wordpress.local'
		);
		$this->assertEquals($expected, $result);
	}
	
}