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

	protected $server;

	public function setUp() {

		parent::setUp();

		global $wp_rest_server;
		$this->server = $wp_rest_server = new WP_Rest_Server;

		do_action('rest_api_init');
	}

	protected function assertResponseStatus( $status, $response ) {
		$this->assertSame( $status, $response->status );
	}

	/** @test */
	public function it_creates_a_post_type_for_post_types() {

		$this->assertTrue( post_type_exists('bbd_pt') );
	}

	/** @test */
	public function it_creates_a_post_type_for_taxonomies() {

		$this->assertTrue( post_type_exists( 'bbd_tax' ) );
	}

	/** @test */
	public function it_does_not_include_bbd_pt_in_rest() {

		$request = new \WP_REST_Request( 'GET', '/wp/v2/bbd_pt' );
		$response = $this->server->dispatch( $request );

		$this->assertResponseStatus( 404, $response );
	}

	/** @test */
	public function it_does_not_include_bbd_tax_in_rest() {

		$request = new \WP_REST_Request( 'GET', '/wp/v2/bbd_tax' );
		$response = $this->server->dispatch( $request );

		$this->assertResponseStatus( 404, $response );
	}

	/** @TODO Conditionally log in before post types/taxonomies are registered on init to test whether users who can edit posts get a 200 status */
	/** @TODO Test filters for post types and taxonomies */
	/** @TODO Test that user-created post types and taxonomies work as expected */
}
