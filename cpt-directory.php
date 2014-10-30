<?php
/**
 * Plugin Name: Custom Post Type Directory
 * Description: Creates a directory based on Custom Post Type, Taxonomy, and Fields
 * Version: 1.61803
 * Author: Big Boom Design
 * Author URI: http://bigboomdesign.com
 */
 
# PHP Classes
if(file_exists(cptdir_folder("lib/CPTDirectory.php"))) require_once cptdir_folder("lib/CPTDirectory.php");
if(file_exists(cptdir_folder("lib/CPTD_pt.php"))) require_once cptdir_folder("lib/CPTD_pt.php");
if(file_exists(cptdir_folder("lib/CPTD_tax.php"))) require_once cptdir_folder("lib/CPTD_tax.php");

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
	# CSS
	wp_enqueue_style("cptdir-admin-css");
}

# Admin Menu Item
add_action('admin_menu', 'cptdir_create_menu');
function cptdir_create_menu() {
	add_menu_page('CPT Directory Settings', 'CPT Directory', 'administrator', 'cptdir-settings-page', 'cptdir_settings_page');
	add_submenu_page( 'cptdir-settings-page', 'CPT Directory Settings', 'Settings', 'administrator', 'cptdir-settings-page', "cptdir_settings_page" );
	add_submenu_page( 'cptdir-settings-page', 'Edit Fields | CPT Directory', 'Fields', 'administrator', 'cptdir-edit-fields', 'cptdir_fields_page' );	
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
# Search Results Page
function cptdir_do_search_results($content){ 
	CPTDirectory::do_search_results();
	return $content;
}
# Set templates for taxonomies
add_filter("taxonomy_template", "cptdir_taxonomy_template");
function cptdir_taxonomy_template($page_template){
    $ctax = cptdir_get_cat_tax();
    $ttax = cptdir_get_tag_tax();
    if(is_tax($ctax->name) || is_tax($ttax->name)){
    	# get the name for the taxonomy
    	$taxname = "";
    	if(is_tax($ctax->name)) $taxname = $ctax->name;
    	elseif(is_tax($ttax->name)) $taxname = $ttax->name;
    	if(!$taxname) return $page_template;
    	# see if the default template file (taxonomy-name.php) exists in the theme folder
    	if(file_exists(get_stylesheet_directory()."/taxonomy-".$taxname.".php")) 
    		return get_stylesheet_directory()."/taxonomy-".$taxname.".php";
    	# if not, create a default category layout
    	else{
    		# We'll need to do something here to define some default behavior (instead of relying on the theme)
    		# For starters, this will fire on every post in the feed (might be used to add fields in place)
    		add_filter("the_content", "cptdir_taxonomy_archive");
    	}
    }
    return $page_template;
}
# This function fires on the_content() for each post in the loop on taxonomy pages, when no template is present in the theme
function cptdir_taxonomy_archive($content){
	# Uncomment below to see example
	#echo "baz.";
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

###
# AJAX calls
###

# Remove Field
function cptdir_remove_field(){
	$field = isset($_POST["cptdir_field"])?$_POST["cptdir_field"]:null;
	$msg = "<p class='cptdir-fail'>Failed to remove field.</p>";
	sleep(1);
	if($field){
		$msg = "<div class='cptdir-success'>Successfully removed field.</div>";
	}
	echo $msg;
	die();
}
add_action("wp_ajax_cptdir_remove_field", "cptdir_remove_field");
add_action("admin_print_scripts-cpt-directory_page_cptdir-edit-fields", "cptdir_remove_field_script");
function cptdir_remove_field_script(){
	wp_enqueue_script("cptdir-remove-field", cptdir_url("js/cptdir_remove_field.js"), array("jquery"));
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
	wp_enqueue_script("cptdir-import-js", cptdir_url("js/cptdir_import.js"), array("jquery"));
}
?>