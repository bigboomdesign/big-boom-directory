<?php
/**
 * Plugin Name: Custom Post Type Directory
 * Description: Directory management system based on Custom Post Types, Taxonomies, and Fields
 * Version: 2.0.0
 * Author: Big Boom Design
 * Author URI: http://bigboomdesign.com
 */
 
/*
* Main Routine
*/



/*
* Helper Functions
*/

# Input a plugin-relative URL or folder path (without slash) and return full plugin URL or folder path
function cptdir_url($s){ return  plugins_url("/".$s, __FILE__); }
function cptdir_dir($s){ return plugin_dir_path(__FILE__).$s; }

function cptdir_success($msg, $tag = 'p', $class=''){ return "<{$tag} class='cptdir-success" . ($class ? " ".$class:null) . "'>{$msg}</{$tag}>"; }
function cptdir_fail($msg, $tag = 'p', $class = ''){ return "<{$tag} class='cptdir-fail" . ($class ? " ".$class:null) . "'>{$msg}</{$tag}>"; }
