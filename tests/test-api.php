<?php

class BPP_Api_Test extends WP_UnitTestCase {

	function test_sample() {
		// replace this with some actual testing code
		$this->assertTrue( true );
	}

	function test_class_exists() {
		$this->assertTrue( class_exists( 'BPP_Api') );
	}

	function test_class_access() {
		$this->assertTrue( bp_premiums()->api instanceof BPP_Api );
	}
}
