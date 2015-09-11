<?php
/**
 * Plugin Name: Custom Post Type Directory
 * Description: Creates a directory based on Custom Post Type, Taxonomy, and Fields
 * Version: 1.11.0
 * Author: Big Boom Design
 * Author URI: http://bigboomdesign.com
 */
 
/*
* Main Routine
*/

# include required classes
require_once cptdir_dir('/lib/class-cptd.php');

# create custom post type and taxonomies
add_action( 'init', array('CPTD', 'setup'));

add_action( 'pre_get_posts', array( 'CPTD', 'pre_get_posts' ) );

# if post type has been set, add meta boxes
if( cptdir_get_pt() && isset( CPTD_Options::$options['use_directory_fields_yes'] ) ) {
	require_once cptdir_dir('/assets/cmb2-functions.php');
}

/*
* Admin Routines
*/
if(is_admin()){
	# js/css
	add_action("admin_enqueue_scripts", array('CPTD', 'admin_enqueue'));
	# menu
	add_action('admin_menu', array('CPTD', 'admin_menu'));
	# settings
	add_action( 'admin_init', array('CPTD_Options','register_settings'));
	# Action links on main Plugins screen
	$plugin = plugin_basename(__FILE__);
	add_filter("plugin_action_links_$plugin", 'cptdir_plugin_actions' );
	function cptdir_plugin_actions($links){
		$settings_link = '<a href="admin.php?page=cptdir-settings-page">Settings</a>';
		array_unshift($links, $settings_link);
		$instructions_link = '<a href="admin.php?page=cptdir-instructions">Instructions</a>';
		array_unshift($links, $instructions_link);
		return $links;
	}	
} # endif: is_admin()

/*
* Front End Routines
*/
else{
	add_action("wp_enqueue_scripts", array('CPTD', 'enqueue'));
	# shortcode for terms list
	add_shortcode('cptd-terms', array('CPTD', 'terms_html'));
	# shortcode for A-Z listing
	add_shortcode('cptd-az-listing', array('CPTD', 'az_html'));
	# shortcode for search widget
	add_shortcode('cptd-search-widget', array('CPTD', 'search_widget'));

	# CPT archive page
	# all views below this one should probably do something like this
	# and maybe be combined together into one hook
	add_action('wp', array('CPTD', 'pt_archive'));
	
	# Single template for CPT
	add_filter("single_template", array('CPTD', "single_template"));
	# taxonomy template for CPT
	add_filter("taxonomy_template", array('CPTD', 'taxonomy_template'));
	# Set page template for various pages
	add_filter( 'page_template', array('CPTD', 'page_templates'));
} # end else: front end

/*
* Helper Functions
*/

# Input a plugin-relative URL or folder path (without slash) and return full plugin URL or folder path
function cptdir_url($s){ return  plugins_url("/".$s, __FILE__); }
function cptdir_dir($s){ return plugin_dir_path(__FILE__).$s; }

function cptdir_success($msg, $tag = 'p', $class=''){ return "<{$tag} class='cptdir-success" . ($class ? " ".$class:null) . "'>{$msg}</{$tag}>"; }
function cptdir_fail($msg, $tag = 'p', $class = ''){ return "<{$tag} class='cptdir-fail" . ($class ? " ".$class:null) . "'>{$msg}</{$tag}>"; }

# Return the post type object if one has been created
function cptdir_get_pt(){
	return CPTD::setup_pt();
}
# Return the heirarchical custom taxonomy object if one exists
function cptdir_get_cat_tax(){ 
	return CPTD::setup_ctax();
}
# Return the non-heirarchical taxonomy object if one exists
function cptdir_get_tag_tax(){
	return CPTD::setup_ttax();
}
# display a field given an array from ACF
function cptdir_field($field, $echo = true){
	# if we're given a string, try to get the field array from ACF
	if(is_string($field)){ if(!($field = function_exists("get_field_object") ? get_field_object($field) : "")) return; }
	# if nothing was found do nothing
	if(!$field || !$field['value']) return;
	$html = CPTD_view::do_single_field($field, $echo);
	return $echo ? null : $html;
}

/**
* Ajax
**/

# Remove Field
add_action("wp_ajax_cptdir_remove_field", "cptdir_remove_field");
function cptdir_remove_field(){
	# field name should be sent in POST from ajax call
	$field = isset($_POST["cptdir_field"])?$_POST["cptdir_field"]:null;
	# get an array of all post IDs in our post type so we don't delete data for other post types
	$aIDs = CPTD::get_all_cpt_ids();
	# delete the field
	if("" != $field && is_string($field)){	
		global $wpdb;
		# delete where meta_key = "field_name" and post_id IN $aIDs
		$query = "DELETE FROM " . $wpdb->prefix . "postmeta WHERE meta_key='$field' AND post_id IN (" . implode(', ', $aIDs) . ")";
		if($nDel = $wpdb->query($wpdb->prepare( $query, '' )))
			$msg = "<div class='cptdir-success'>Successfully removed $nDel rows.<br />";
		else{ $msg = "<div class='cptdir-fail'>We didn't find any fields to delete.<br /><br />"; }
		
		# Check if field is still set in Advanced Custom Fields
		if($result = $wpdb->query($wpdb->prepare("SELECT meta_key FROM " . $wpdb->prefix . "postmeta WHERE meta_key = " . "'_".$field."'", '')))
			$msg .= "This field will show up until you remove it from Advanced Custom Fields.";
		$msg .= "</div>";
	}
	echo $msg;
	die();
}
# Remove All Fields
add_action('wp_ajax_cptdir_remove_all_field_data', 'cptdir_remove_all_fields');
function cptdir_remove_all_fields(){
	global $wpdb;
	# get post IDs for both published and unpublished
	$bPub = false;
	$aIDs = CPTD::get_all_cpt_ids($bPub);
	# number of rows deleted
	$nDel = 0;
	if($aIDs){
		# Delete
		$query = "DELETE FROM " . $wpdb->prefix . "postmeta WHERE post_id IN(" . implode(", ", $aIDs) . ")";
		$nDel = $wpdb->query( $wpdb->prepare($query, '') );
	}
	# Message to display
	$msg = "";
	if($nDel) $msg = "<div class='cptdir-success'>Successfully deleted " . $nDel . " rows.</div>";
	else $msg = "<div class='cptdir-fail'>We didn't find any fields to delete</div>";
	echo $msg;
	die();
}
# Remove unpublished
add_action('wp_ajax_cptdir_remove_unpublished', 'cptdir_remove_unpublished');
function cptdir_remove_unpublished(){
	global $wpdb;
	# get post IDs for both published and unpublished
	$bPub = false;
	$aIDs = CPTD::get_all_cpt_ids($bPub);
	# count total fields and posts deleted
	$nDelField = 0;
	$nDelPost = 0;	
	if($aIDs){
		$pt = cptdir_get_pt();
		# Get all IDs from posts whose parent has our post type (revisions, auto-drafts)
		# as well as all unpublished posts from our post type
		$query = "SELECT ID FROM " . $wpdb->prefix . "posts WHERE ";
			$query .= "post_parent IN (" . implode(', ', $aIDs) . ") ";
			$query .= "OR ( post_type='" . $pt->name . "' ";
				$query .= "AND post_status != 'publish' ";
			$query .= ")";
		$aPosts = $wpdb->get_results($wpdb->prepare($query, ''));
		# loop through results and clear custom field data as well as posts
		if($aPosts) foreach($aPosts as $post){
			# remove fields
			$thisDelField = 0;
			$query = "DELETE FROM ". $wpdb->postmeta . " WHERE post_id=" . $post->ID;
			$thisDelField = $wpdb->query($wpdb->prepare($query, ''));
			$nDelField += $thisDelField;
		
			# remove post
			$thisDelPost = 0;
			$query = "DELETE FROM " . $wpdb->posts . " WHERE ID=" . $post->ID;
			$thisDelPost = $wpdb->query($wpdb->prepare($query, ''));
			$nDelPost += $thisDelPost;
		
		}
	} #endif: IDs were found for post type
	# Message to display
	$msg = "";
	if($nDelPost) $msg = "<div class='cptdir-success'>Successfully deleted " . $nDelPost . " posts and " . $nDelField . " fields.</div>";
	else $msg = "<div class='cptdir-fail'>We didn't find any posts to delete</div>";
	echo $msg;
	die();
}
# Remove published
add_action('wp_ajax_cptdir_remove_published', 'cptdir_remove_published');
function cptdir_remove_published(){
	global $wpdb;
	# get published post IDs for our PT
	$aIDs = CPTD::get_all_cpt_ids();
	# Clear custom fields data for published posts
	$nDelField = 0;
	$nDelPost = 0;
	if($aIDs) foreach($aIDs as $id){
		# remove fields
		$thisDelField = 0;
		$query = "DELETE FROM ". $wpdb->postmeta . " WHERE post_id=" . $id;
		$thisDelField = $wpdb->query($wpdb->prepare($query, ''));
		$nDelField += $thisDelField;
		
		# remove post
		$thisDelPost = 0;
		$query = "DELETE FROM " . $wpdb->posts . " WHERE ID=" . $id;
		$thisDelPost = $wpdb->query($wpdb->prepare($query, ''));
		$nDelPost += $thisDelPost;
	}
	# Message to display
	$msg = "";
	if($nDelPost) $msg = "<div class='cptdir-success'>Successfully deleted " . $nDelPost . " posts and " . $nDelField . " fields.</div>";
	else $msg = "<div class='cptdir-fail'>We didn't find any posts to delete</div>";
	echo $msg;	
	die();
}
# Import
function cptdir_import_js(){
	require_once cptdir_dir("lib/class-cptd-import.php");
	$post_type = sanitize_text_field($_POST['post_type']);
	$importer = new CPTD_import( $post_type, cptdir_get_cat_tax(), cptdir_get_tag_tax() );
	$importer->do_import_content();
	die();
}
add_action("wp_ajax_cptdir_import_js", "cptdir_import_js");
add_action("admin_print_scripts-cpt-directory_page_cptdir-import", "cptdir_import_js_script");
function cptdir_import_js_script(){
	wp_enqueue_script("cptdir-import-js", cptdir_url("js/cptdir-import.js"), array("jquery"));
}

# Map custom fields to ACF
add_action('wp_ajax_map-custom-fields-to-acf', 'map_fields_to_acf');
function map_fields_to_acf(){
	global $wpdb;
	$n = 0;
	$fields = CPTD::get_acf_fields();
	foreach($fields as $field){		
		$sql = "SELECT post_id FROM ".$wpdb->postmeta." WHERE meta_key='" . $field['name'] . "'";
		$r = $wpdb->get_results($wpdb->prepare( $sql, '' ));
		foreach($r as $row){
			# for `my_field`, we need to add something like 
			#  ( _myfield => field_2387f8790sdf )
			if(update_post_meta($row->post_id, '_'.$field['name'], $field['key'])) $n++;
		}		
	}
	echo "Updated $n fields";
	die();
}
?>