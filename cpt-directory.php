<?php
/**
 * Plugin Name: Custom Post Type Directory
 * Description: Directory management system based on Custom Post Types, Taxonomies, and Fields
 * Version: 2.0.0.32.3
 * Author: Big Boom Design
 * Author URI: https://bigboomdesign.com
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

require_once cptd_dir('/lib/class-cptd.php');
CPTD::load_classes();


/**
 * Actions
 */

add_action( 'init', array( 'CPTD', 'init' ) );
add_action( 'pre_get_posts', array( 'CPTD', 'pre_get_posts' ) );
add_action( 'widgets_init', array( 'CPTD', 'widgets_init' ) );

/**
 * Admin Routines
 */
if( is_admin() ) {
	
	# the plugin core admin class
	require_once cptd_dir( '/lib/admin/class-cptd-admin.php' );

	# CMB2, which handles meta boxes for CPTD post type post edit screen
	require_once cptd_dir( '/lib/admin/class-cptd-meta-boxes.php' );
	require_once cptd_dir( '/assets/cmb2/init.php' );
	
	CPTD_Admin::init();
	CPTD_Ajax::add_actions();

} # end if: is_admin()

/**
 * Front End Routines
 */
else{
	
	require_once cptd_dir('/lib/class-cptd-view.php');
	require_once cptd_dir('/lib/class-cptd-field.php');
	
	# the front end view object ( initialized via `wp` action )
	global $cptd_view;
	$cptd_view = null;

	add_action( 'wp', array( 'CPTD', 'wp' ) );

}

/**
 * Helper Functions
 * 
 * - is_cptd_view()
 * - cptd_field()
 * - cptd_get_field_html()
 *
 * - cptd_url()
 * - cptd_dir()
 *
 * - cptd_success()
 * - cptd_fail()
 */

/**
 * Whether or not the main query is for a CPTD object
 *
 * @return 	bool	Returns true when viewing any the following:
 *
 * - Single view for CPTD user-created post type 
 * - Post type archive for CPTD user-created post type
 * - Term archive for CPTD user-created taxonomy term
 *
 * @since 	2.0.0
 */
function is_cptd_view() {

	# load view info if it hasn't been done already
	if( null === CPTD::$is_cptd ) CPTD::load_view_info();

	return CPTD::$is_cptd;

} # end: is_cptd_view()

/**
 * Render HTML for a single field for a single post. 
 * 
 * Filters through the following:
 * 	
 * 	- cptd_field_value_{$field_key}
 * 	- cptd_field_label_{$field_key}
 * 	- cptd_field_wrap_{$field_key}
 *
 * @param 	int|string 				$post_id		The post ID to get the field value from
 * @param 	string|CPTD_Field		$field			The field key or object to display HTML for
 *
 * @since 	2.0.0
 */
function cptd_field( $post_id, $field ) {

	if( is_string( $field ) ) $field = new CPTD_Field( $field );
	$field->get_html( true, $post_id );
}

/**
 * Return HTML for a single field for a single post.
 *
 * Filters through the following:
 *
 * 	- cptd_field_value_{$field_key}
 * 	- cptd_field_label_{$field_key}
 * 	- cptd_field_wrap_{$field_key}
 *
 * @param 	int|string 				$post_id		The post ID to get the field value from
 * @param 	string|CPTD_Field		$field			The field key or object to get HTML for
 *
 * @return 	string
 * @since 	2.0.0
 */
function cptd_get_field_html( $post_id, $field ) {

	if( is_string( $field ) ) $field = new CPTD_Field( $field );
	return $field->get_html( false, $post_id );
}

/**
 * Return the URL (cptd_url) or folder path (cptd_dir) for this plugin
 * 
 * @param 	string 	$s 	Optional string to append to the path
 * @since 	2.0.0
 */
function cptd_url($s){ return  plugins_url($s, __FILE__); }
function cptd_dir($s){ return plugin_dir_path(__FILE__).$s; }

/**
 * Display a success (cptd_success) or failure (cptd_fail) message with a given tag and CSS class
 * 
 * @param 	string 	$msg 	The message to display
 * @param 	string 	$tag 	The HTML tag to wrap the message (default: 'p')
 * @param 	string 	$class 	Optional CSS class to add to the element
 * @return 	string
 * @since 	2.0.0
 */
function cptd_success($msg, $tag = 'p', $class=''){ return "<{$tag} class='cptd-success" . ($class ? " ".$class:null) . "'>{$msg}</{$tag}>"; }
function cptd_fail($msg, $tag = 'p', $class = ''){ return "<{$tag} class='cptd-fail" . ($class ? " ".$class:null) . "'>{$msg}</{$tag}>"; }
