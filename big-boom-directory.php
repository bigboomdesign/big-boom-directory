<?php
/**
 * Plugin Name: Big Boom Directory
 * Description: Directory management system based on Custom Post Types, Taxonomies, and Fields
 * Version: 2.0.0.33.1
 * Author: Big Boom Design
 * Author URI: https://bigboomdesign.com
 * Text Domain: bbd
 */
 
/**
 * Main Routine
 * 
 * - Dependencies
 * - Actions
 * - Admin Routines
 * - Front End Routines
 * - Helper Functions
 */

/**
 * Dependencies
 * 
 * Other than core plugin classes, the dependencies are:
 *
 * - Extended CPT's
 * @link 	https://github.com/johnbillion/extended-cpts
 *
 * - Extended Taxonomies
 * @link	https://github.com/johnbillion/extended-taxos
 *
 * - CMB2, on the admin side
 * @link	https://github.com/WebDevStudios/cmb2
 */

require_once bbd_dir('/lib/class-bbd.php');
BBD::load_classes();


/**
 * Actions
 */

add_action( 'init', array( 'BBD', 'init' ) );
add_action( 'pre_get_posts', array( 'BBD', 'pre_get_posts' ) );
add_action( 'widgets_init', array( 'BBD', 'widgets_init' ) );

/**
 * Admin Routines
 */
if( is_admin() ) {
	
	# the plugin core admin class
	require_once bbd_dir( '/lib/admin/class-bbd-admin.php' );

	# CMB2, which handles meta boxes for BBD post type post edit screen
	require_once bbd_dir( '/assets/cmb2/init.php' );
	require_once bbd_dir( '/lib/admin/class-bbd-meta-boxes.php' );
	
	BBD_Admin::init();
	BBD_Ajax::add_actions();

} # end if: is_admin()

/**
 * Front End Routines
 */
else{
	
	require_once bbd_dir('/lib/class-bbd-view.php');
	
	# the front end view object ( initialized via `wp` action )
	global $bbd_view;
	$bbd_view = null;

	add_action( 'wp', array( 'BBD', 'wp' ) );

}

/**
 * Helper Functions
 * 
 * - is_bbd_view()
 * - bbd_field()
 * - bbd_get_field_html()
 *
 * - bbd_url()
 * - bbd_dir()
 *
 * - bbd_success()
 * - bbd_fail()
 */

/**
 * Whether or not the main query is for a BBD object
 *
 * @return 	bool	Returns true when viewing any the following:
 *
 * - Single view for BBD user-created post type 
 * - Post type archive for BBD user-created post type
 * - Term archive for BBD user-created taxonomy term
 *
 * @since 	2.0.0
 */
function is_bbd_view() {

	# load view info if it hasn't been done already
	if( null === BBD::$is_bbd ) BBD::load_view_info();

	return BBD::$is_bbd;

} # end: is_bbd_view()

/**
 * Render HTML for a single field for a single post. 
 * 
 * Filters through the following:
 * 	
 * 	- bbd_field_value_{$field_key}
 * 	- bbd_field_label_{$field_key}
 * 	- bbd_field_wrap_{$field_key}
 *
 * @param 	int|string 				$post_id		The post ID to get the field value from
 * @param 	string|BBD_Field		$field			The field key or object to display HTML for
 *
 * @since 	2.0.0
 */
function bbd_field( $post_id, $field ) {

	if( is_string( $field ) ) $field = new BBD_Field( $field );
	$field->get_html( true, $post_id );

} # end: bbd_field()

/**
 * Return HTML for a single field for a single post.
 *
 * Filters through the following:
 *
 * 	- bbd_field_value_{$field_key}
 * 	- bbd_field_label_{$field_key}
 * 	- bbd_field_wrap_{$field_key}
 *
 * @param 	int|string 				$post_id		The post ID to get the field value from
 * @param 	string|BBD_Field		$field			The field key or object to get HTML for
 *
 * @return 	string
 * @since 	2.0.0
 */
function bbd_get_field_html( $post_id, $field ) {

	if( is_string( $field ) ) $field = new BBD_Field( $field );
	return $field->get_html( false, $post_id );

} # end: bbd_get_field_html()

/**
 * Return the URL (bbd_url) or folder path (bbd_dir) for this plugin
 * 
 * @param 	string 	$s 	Optional string to append to the path
 * @since 	2.0.0
 */
function bbd_url( $s ){ return plugins_url( $s, __FILE__ ); }
function bbd_dir( $s ){ return plugin_dir_path( __FILE__ ) . $s; }

/**
 * Display a success (bbd_success) or failure (bbd_fail) message with a given tag and CSS class
 * 
 * @param 	string 	$msg 	The message to display
 * @param 	string 	$tag 	The HTML tag to wrap the message (default: 'p')
 * @param 	string 	$class 	Optional CSS class to add to the element
 * @return 	string
 * @since 	2.0.0
 */
function bbd_success( $msg, $tag = 'p', $class='' ) { return "<{$tag} class='bbd-success" . ( $class ? " ".$class:null ) . "'>{$msg}</{$tag}>"; }
function bbd_fail( $msg, $tag = 'p', $class = '' ) { return "<{$tag} class='bbd-fail" . ( $class ? " ".$class:null ) . "'>{$msg}</{$tag}>"; }
