<?php
/**
 * Plugin Name: Custom Post Type Directory
 * Description: Creates a directory based on Custom Post Type, Taxonomy, and Fields
 * Version: 0.1.0
 * Author: Big Boom Design
 * Author URI: http://bigboomdesign.com
 */
 
# PHP Classes
$aCPTD_classes = array("CPTDirectory", "CPTD_pt", "CPTD_pt", "CPTD_tax", "CPTD_view");
foreach($aCPTD_classes as $class) if(file_exists(cptdir_folder("lib/".$class.".php"))) require_once cptdir_folder("lib/".$class.".php");

# Widget
require_once cptdir_folder("lib/CPTD_search_widget.php");

###
# Javascripts and Styles
###

# Admin
wp_register_style("cptdir-admin-css", cptdir_url("css/cptdir-admin.css"));
wp_register_style("cptdir-css", cptdir_url("css/cptdir.css"));
add_action("wp_enqueue_scripts", "cptdir_enqueue_scripts");
add_action("admin_enqueue_scripts", "cptdir_enqueue_admin_scripts");
function cptdir_enqueue_scripts(){
	# CSS
	wp_enqueue_style("cptdir-css");
}
function cptdir_enqueue_admin_scripts(){
	$screen = get_current_screen();
	# CSS
	wp_enqueue_style("cptdir-admin-css");
	# JS
	if($screen->id == 'cpt-directory_page_cptdir-fields'){
		wp_enqueue_script('cptdir-fields-js', cptdir_url('js/cptdir-fields.js'), array('jquery'));
	}	
	if($screen->id == 'cpt-directory_page_cptdir-cleanup'){
		wp_enqueue_script('cptdir-cleanup-js', cptdir_url('js/cptdir-cleanup.js'), array('jquery'));
	}
	
}

# Admin Menu Item
add_action('admin_menu', 'cptdir_create_menu');
function cptdir_create_menu() {
	add_menu_page('CPT Directory Settings', 'CPT Directory', 'administrator', 'cptdir-settings-page', 'cptdir_settings_page');
	add_submenu_page( 'cptdir-settings-page', 'CPT Directory Settings', 'Settings', 'administrator', 'cptdir-settings-page', "cptdir_settings_page" );
	add_submenu_page( 'cptdir-settings-page', 'Edit Fields | CPT Directory', 'Fields', 'administrator', 'cptdir-fields', 'cptdir_fields_page' );	
	add_submenu_page( 'cptdir-settings-page', 'Clean Up | CPT Directory', 'Clean Up', 'administrator', 'cptdir-cleanup', 'cptdir_cleanup_page' );	
	add_submenu_page("cptdir-settings-page", "Import | CPT Directory", "Import", "administrator", "cptdir-import", "cptdir_import_page");
	add_action( 'admin_init', 'cptdir_register_settings' );
}

# Register Plugin Settings
function cptdir_register_settings() {
	$cpt_settings = array(
		"cpt_sing", "cpt_pl", "cpt_slug", 
		"cpt_ctax_sing", "cpt_ctax_pl", "cpt_ctax_slug", "cpt_ttax_sing", "cpt_ttax_pl", "cpt_ttax_slug",
		"cpt_search_page"
	);
	foreach($cpt_settings as $setting){
		register_setting("cptdir-settings-group", $setting, cptdir_get_validation_callback($setting));
	}
}
function cptdir_get_validation_callback($setting){
	$aSlugValidate = array("cpt_slug", "cpt_ctax_slug", "cpt_ttax_slug");
	if(in_array($setting, $aSlugValidate)){ return "cptdir_validate_slug"; }
}
function cptdir_validate_slug($input){ return CPTDirectory::clean_str_for_url($input); }
function cptdir_settings_page() { CPTDirectory::do_settings_page(); }
function cptdir_fields_page(){ CPTDirectory::do_fields_page(); }
function cptdir_cleanup_page(){ CPTDirectory::do_cleanup_page(); }
function cptdir_import_page(){ 
	require_once cptdir_folder("lib/CPTD_import.php"); 
	$importer = new CPTD_import( cptdir_get_pt(), cptdir_get_cat_tax(), cptdir_get_tag_tax() );
	$importer->do_import_page();
}

# Create custom post type and taxonomies
global $cptdir_pt;
global $cptdir_ctax;
global $cptdir_ttax;

add_action( 'init', 'cptdir_create_post_type');
function cptdir_create_post_type() {
	global $cptdir_pt;
	$cptdir_pt = cptdir_get_pt();
	# If post type exists
	if($cptdir_pt){
		$cptdir_pt->register_pt();

		# Create custom heirarchical taxonomy
		global $cptdir_ctax;
		if($cptdir_ctax = cptdir_get_cat_tax()) 
			$cptdir_ctax->register_tax();
			
		# Create custom non-heirarchical taxonomy
		global $cptdir_ttax;
		if($cptdir_ttax = cptdir_get_tag_tax())
			$cptdir_ttax->register_tax();
	}
}
# Single template for CPT
add_filter("single_template", "cptdir_single_template");
function cptdir_single_template($single_template){
	$pt = cptdir_get_pt();
	# do nothing if we're not viewing a single listing of our PT
	if(!is_singular($pt->name)) return $single_template;
	
	# add the_content filter for post content
	add_filter("the_content", "cptdir_do_single");
	return $single_template;
}
## the_content filter for single listing
function cptdir_do_single($content){
	# if theme has custom content function, do that and return
	## note that custom function has option to return $content
	if(function_exists("cptdir_custom_single")){ return cptdir_custom_single($content); }

	# otherwise set up default view
	return cptdir_default_field_view($content);
}
# default field view (can be called by theme if needed from inside cptdir_custom_single)
function cptdir_default_field_view($content = "", $type = "single", $callback = ""){
	global $post;
	$view = new CPTD_view(array("ID" => $post->ID, "type"=>$type));
	$view->do_fields($callback);
	return $content;
}

# Set templates for taxonomy archives
add_filter("taxonomy_template", "cptdir_taxonomy_template");
function cptdir_taxonomy_template($page_template){
	# do nothing if we're not viewing a taxonomy archive
    if(!is_tax()) return $page_template;
    
    # get custom taxonomy objects and return if we're not viewing either of their archive pages
    $ctax = cptdir_get_cat_tax();
    $ttax = cptdir_get_tag_tax();
    # get taxonomy name
	if(
		!(
			($bCtax = ($ctax && is_tax($ctax->name)))
				|| ($bTtax = ($ttax && is_tax($ttax->name)))
		)
	)
	return $page_template;
   	$taxname = $bCtax ? $ctax->name : ($bTtax ? $ttax->name : "");
    if(!$taxname) return $page_template;

	# the_content for taxonomy archive post content
	add_filter("the_content", "cptdir_taxonomy_content");
    return $page_template;
}
# This function fires on the_content() for each post in the loop on taxonomy pages, when no template is present in the theme
function cptdir_taxonomy_content($content){
	# if theme has custom content function, do that and return
	## note that custom function has option to return $content
	if(function_exists("cptdir_custom_taxonomy_content")){ return cptdir_custom_taxonomy_content($content); }
	
	# otherwise set up default view
	global $post;
	$tax = cptdir_get_cat_tax() ? cptdir_get_cat_tax() : (cptdir_get_tag_tax() ? cptdir_get_tag_tax() : "");
	if(!is_object($tax)) return $content;
	
	return cptdir_default_field_view($content, "multi");
}

# Set page template for various pages
add_filter( 'page_template', 'cptdir_page_templates' );
function cptdir_page_templates( $page_template ){
	# search results
	$pg_id = get_option("cpt_search_page");
    if ( $pg_id && is_page( $pg_id ) ) {
    	# Do search results when the_content() is called
        add_filter("the_content", "cptdir_do_search_results");
        return $page_template;
    }   
    return $page_template;
}
## Search Results Page
function cptdir_do_search_results($content){ 
	CPTDirectory::do_search_results();
	return $content;
}

###
# Helper Functions
###

# Input a plugin-relative URL or folder path (without slash) and return full plugin URL or folder path
function cptdir_url($s){ return  plugins_url("/".$s, __FILE__); }
function cptdir_folder($s){ return plugin_dir_path(__FILE__).$s; }
function cptdir_success($msg, $tag = "p", $class=""){ return "<{$tag} class='cptdir-success" . ($class ? " ".$class:null) . "'>{$msg}</{$tag}>"; };
function cptdir_fail($msg){ return "<p class='cptdir-fail'>{$msg}</p>"; };

# Return the post type object if one has been created
function cptdir_get_pt(){ 
	global $cptdir_pt;
	if($cptdir_pt) return $cptdir_pt;
	if(
		!($sing = get_option("cpt_sing"))
		 || !($pl = get_option("cpt_pl"))
		 || !($slug = get_option("cpt_slug"))
		 || !class_exists("CPTD_pt")
	) return false;
	$obj = new CPTD_pt($slug, $sing, $pl);
	return $obj;
}
# Return the heirarchical custom taxonomy object if one exists
function cptdir_get_cat_tax(){ 
	global $cptdir_ctax;
	if($cptdir_ctax){ return $cptdir_ctax; }
	if(
		!($sing = get_option("cpt_ctax_sing"))
		  || !($pl = get_option("cpt_ctax_pl"))
		  || !($slug = get_option("cpt_ctax_slug"))
		  || !($pt = cptdir_get_pt())
	) { return false;}
	return new CPTD_tax($slug, $sing, $pl, $pt->name, true );
}
# Return the non-heirarchical taxonomy object if one exists
function cptdir_get_tag_tax(){
	global $cptdir_ttax;
	if($cptdir_ttax) return $cptdir_ttax;
	if(
		!($sing = get_option("cpt_ttax_sing"))
		|| !($pl = get_option("cpt_ttax_pl"))
		|| !($slug = get_option("cpt_ttax_slug"))
		|| !($pt = cptdir_get_pt())
	) return false;	
	return new CPTD_tax($slug, $sing, $pl, $pt->name, false);
}
# display a field given an array from ACF
function cptdir_field($field){
	# if we're given a string, try to get the field array from ACF
	if(is_string($field)){ if(!($field = function_exists("get_field_object") ? get_field_object($field) : "")) return; }
	# if nothing was found do nothing
	if(!$field || !$field['value']) return;
	CPTD_view::do_single_field($field);
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
	$aIDs = CPTDirectory::get_all_cpt_ids();
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
	$aIDs = CPTDirectory::get_all_cpt_ids($bPub);
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
	$aIDs = CPTDirectory::get_all_cpt_ids($bPub);
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
	$aIDs = CPTDirectory::get_all_cpt_ids();
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
	require_once cptdir_folder("lib/CPTD_import.php");
	$importer = new CPTD_import( cptdir_get_pt(), cptdir_get_cat_tax(), cptdir_get_tag_tax() );
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
	$fields = CPTDirectory::get_acf_fields();
	foreach($fields as $field){		
		$sql = "SELECT post_id FROM ".$wpdb->postmeta." WHERE meta_key='" . $field['name'] . "'";
		$r = $wpdb->get_results($wpdb->prepare( $sql ));
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