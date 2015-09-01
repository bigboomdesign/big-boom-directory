<?php
/**
 * Plugin Name: Custom Post Type Directory
 * Description: Directory management system based on Custom Post Types, Taxonomies, and Fields
 * Version: 2.0.0.2.0
 * Author: Big Boom Design
 * Author URI: http://bigboomdesign.com
 */
 
/*
* Main Routine
*/
if(!cptdir_should_load()) return;

# dependencies
## CPTD classes
require_once cptdir_dir('/lib/class-cptd.php');
require_once cptdir_dir('lib/class-cptd-helper.php');
require_once cptdir_dir('/lib/class-cptd-post.php');
require_once cptdir_dir('/lib/class-cptd-pt.php');
require_once cptdir_dir('/lib/class-cptd-tax.php');
require_once cptdir_dir('/lib/class-cptd-ajax.php');

## Extended Post Types
require_once cptdir_dir('/assets/extended-cpts.php');
require_once cptdir_dir('/assets/extended-taxos.php');

# Actions
## Register user-defined post types
add_action('init', array('CPTD_Helper', 'register'));

/* 
* Admin 
*/
if(is_admin()){
	
	require_once cptdir_dir('/lib/class-cptd-admin.php');
	require_once cptdir_dir('/lib/class-cptd-options.php');
	CPTD_Admin::init();
	CPTD_Ajax::add_actions();
}

/*
* Front end
*/
else{

}

/*
* Helper Functions
*/

# whether or not this plugin should load
function cptdir_should_load(){ return true; }

# Input a plugin-relative URL or folder path (without slash) and return full plugin URL or folder path
function cptdir_url($s){ return  plugins_url("/".$s, __FILE__); }
function cptdir_dir($s){ return plugin_dir_path(__FILE__).$s; }

function cptdir_success($msg, $tag = 'p', $class=''){ return "<{$tag} class='cptdir-success" . ($class ? " ".$class:null) . "'>{$msg}</{$tag}>"; }
function cptdir_fail($msg, $tag = 'p', $class = ''){ return "<{$tag} class='cptdir-fail" . ($class ? " ".$class:null) . "'>{$msg}</{$tag}>"; }
