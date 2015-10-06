<?php
/**
 * Plugin Name: Custom Post Type Directory
 * Description: Directory management system based on Custom Post Types, Taxonomies, and Fields
 * Version: 2.0.0.6.1
 * Author: Big Boom Design
 * Author URI: http://bigboomdesign.com
 */
 
/**
 * Main Routine
 */

/**
 * Dependencies
 */

# CPTD classes
require_once cptd_dir('/lib/class-cptd.php');
require_once cptd_dir('/lib/class-cptd-helper.php');
require_once cptd_dir('/lib/class-cptd-post.php');
require_once cptd_dir('/lib/class-cptd-pt.php');
require_once cptd_dir('/lib/class-cptd-tax.php');
require_once cptd_dir('/lib/class-cptd-ajax.php');

# Extended Post Types & Taxonomies
if( ! function_exists( 'register_extended_post_type' ) ) require_once cptd_dir('/assets/extended-cpts.php');
if( ! function_exists( 'register_extended_taxonomy' ) ) require_once cptd_dir('/assets/extended-taxos.php');


/**
 * Actions
 */

# Register user-defined post types
add_action('init', array('CPTD', 'load_cptd_post_data'));
add_action('init', array('CPTD_Helper', 'register'));


/**
 * Admin Routines
 */
if(is_admin()){
	
	require_once cptd_dir('/lib/admin/class-cptd-admin.php');
	require_once cptd_dir('/lib/class-cptd-options.php');
	require_once cptd_dir('/assets/cmb2/init.php');
	
	CPTD_Admin::init();
	CPTD_Ajax::add_actions();
}

/**
 * Front End Routines
 */
else{
	
	add_action( 'pre_get_posts', array( 'CPTD', 'pre_get_posts' ) );
}

/**
 * Helper Functions
 * 
 * - cptd_should_load()
 * - is_cptd_view()
 * - cptd_url()
 * - cptd_dir()
 * - cptd_success()
 * - cptd_fail()
 */

/** 
 * Whether or not this plugin should load
 * @since 	2.0.0
 */
function cptd_should_load(){ 
	return true; 
}

/**
 * Whether or not a query is for a CPTD object
 *
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
function cptd_success($msg, $tag = 'p', $class=''){ return "<{$tag} class='cptdir-success" . ($class ? " ".$class:null) . "'>{$msg}</{$tag}>"; }
function cptd_fail($msg, $tag = 'p', $class = ''){ return "<{$tag} class='cptdir-fail" . ($class ? " ".$class:null) . "'>{$msg}</{$tag}>"; }
