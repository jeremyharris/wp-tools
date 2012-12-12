<?php

require dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'libs' . DIRECTORY_SEPARATOR . 'Shell.php';
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'WPToolsDatabaseTestCase.php';

/**
 * Tests database interactions with single sites
 */
class ShellDbSingleTest extends WPToolsDatabaseTestCase {
	
/**
 * WP install suffix, for getting the right `wp-config.php`
 * 
 * @var string
 */
	protected $install = 'single';
	
	public function testGetBlogs() {
		$ds = $this->getDataSet(array(
			'single_options'
		));
		$this->loadDataSet($ds);
		
		$result = $this->Shell->getBlogs();
		$expected = array(
			0 => 'wordpress.local'
		);
		$this->assertEquals($expected, $result);
	}
	
}