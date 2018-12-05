<?php
/**
 * Class CorePostTypesTest
 *
 * @package BigBoomDirectory
 */

/**
 * Sample test case.
 */
class PostTypesTest extends WP_UnitTestCase {

	/** @test */
	public function it_creates_a_post_type_for_post_types() {

		$this->assertTrue( post_type_exists('bbd_pt') );
	}

	/** @test */
	public function it_creates_a_post_type_for_taxonomies() {

		$this->assertTrue( post_type_exists( 'bbd_tax' ) );
	}
}
