<?php
/**
 * Performs helper functions for the plugin's various components
 *
 * @since 	2.0.0
 */
class CPTD_Helper{

	/**
	 * Class Methods
	 *
	 * - get_post_field()
	 * - clean_str_for_url()
	 * - clean_str_for_field()
	 * - get_field_array()
	 * - get_choice_array()
	 *
	 * - register()
	 * - get_all_field_keys()
	 * - get_image_sizes()
	 */
	
	/**
	 * Check if a $_POST value is empty and return sanitized value
	 *
	 * @param 	string 	$field 		The key to check within the $_POST array
	 * @since 	2.0.0
	 */
	public static function get_post_field($field){
		if(empty($_POST[$field]) || trim($_POST[$field]) == '') return '';
		return sanitize_text_field($_POST[$field]);
	}
	
	/**
	 * Return a URL-friendly version of a string ( letters/numbers/hyphens only ), replacing unfriendly chunks with a single dash
	 *
	 * @param 	string 	$input 		The string to clean for URL usage
	 * @since 	2.0.0
	 */
	public static function clean_str_for_url( $input ){
		if( $input == "" ) return "";
		$output = trim( strtolower( $input ) );
		$output = preg_replace( "/\s\s+/" , " " , $output );					
		$output = preg_replace( "/[^a-zA-Z0-9 \-]/" , "",$output );	
		$output = preg_replace( "/--+/" , "-",$output );
		$output = preg_replace( "/ +- +/" , "-",$output );
		$output = preg_replace( "/\s\s+/" , " " , $output );	
		$output = preg_replace( "/\s/" , "-" , $output );
		$output = preg_replace( "/--+/" , "-" , $output );
		$nWord_length = strlen( $output );
		if( $output[ $nWord_length - 1 ] == "-" ) { $output = substr( $output , 0 , $nWord_length - 1 ); } 
		return $output;
	}

	/**
	 * Return a field-key-friendly version of a string ( letters/numbers/hyphens/underscores only ), replacing unfriendly chunks with a single underscore
	 *
	 * @param 	string 	$input 		The string to clean for field key usage
	 * @since 	2.0.0
	 */
	public static function clean_str_for_field($input){
		if( $input == "" ) return "";
		$output = trim( strtolower( $input ) );
		$output = preg_replace( "/\s\s+/" , " " , $output );					
		$output = preg_replace( "/[^a-zA-Z0-9 \-_]/" , "",$output );
		$output = preg_replace( "/--+/" , "-",$output );
		$output = preg_replace( "/__+/" , "_",$output );
		$output = preg_replace( "/ +- +/" , "-",$output );
		$output = preg_replace( "/ +_ +/" , "_",$output );
		$output = preg_replace( "/\s\s+/" , " " , $output );	
		$output = preg_replace( "/\s/" , "_" , $output );
		$output = preg_replace( "/--+/" , "-" , $output );
		$output = preg_replace( "/__+/" , "_" , $output );
		$nWord_length = strlen( $output );
		if( $output[ $nWord_length - 1 ] == "-" || $output[ $nWord_length - 1 ] == "_" ) { $output = substr( $output , 0 , $nWord_length - 1 ); } 
		return $output;		
	}

	/**
	 * Generate a label, value, etc. for any given setting 
	 * input can be a string or array and a full, formatted array will be returned
	 * If $field is a string we assume the string is the label
	 * if $field is an array we assume that at least a label exists
	 * optionally, the parent field's name can be passed for better labelling
	 *
	 * @param	(array|string)		$field {
	 *		The key string or field array that we are completing
	 *
	 * 		@type 	string 		$type 		The field type (default: text)
	 * 		@type 	string 		$id			The ID attribute 
	 * 		@type	mixed 		$value		The field value
	 * 		@type 	string 		$label		The label for the field
	 * 		@type 	string 		$name		The input name (default: $id)
	 * 		@type 	array 		$choices	Choices for the field value
	 *
	 * }
	 * @param 	string 	$parent_name 	Added for child fields to identify their parent
	 * @since 	2.0.0
	 */
	public static function get_field_array( $field, $parent_name = ''){
		$id = $parent_name ? $parent_name.'_' : '';
		if(!is_array($field)){
			$id .= self::clean_str_for_field($field);
			$out = array();
			$out['type'] = 'text';
			$out['label'] = $field;
			$out['value'] = $id;
			$out['id'] .= $id;
			$out['name'] = $id;
		}
		else{
			# do nothing if we don't have a label or name or ID
			if(
				!array_key_exists('label', $field) 
				&& !array_key_exists('name', $field)
				&& !array_key_exists('id', $field)
			) return $field;
			
			$id .= array_key_exists('name', $field) ? 
				$field['name'] 
				: (
					array_key_exists('id', $field) ?
					$field['id']
					: self::clean_str_for_field($field['label'])
			);
			
			$out = $field;
			if(!array_key_exists('id', $out)) $out['id'] = $id;
			if(!array_key_exists('name', $out)) $out['name'] = $id;
			# make sure all choices are arrays
			if(array_key_exists('choices', $field)){
				$out['choices'] = self::get_choice_array($field);
			}
		}
		return $out;
	}

	/**
	 * Get array of choices for a setting field
	 * This allows choices to be set as strings or arrays with detailed properties, 
	 * so that either way our options display function will have the data it needs
	 *
	 * @param 	array 	$setting 	The field array to get choices for (see get_field_array)
	 * @since 	2.0.0
	 */
	public static function get_choice_array($setting){
		extract($setting);
		if(!isset($choices)) return;
		$out = array();
		if(!is_array($choices)){
			$out[] = array(
				'id' => $name.'_'.self::clean_str_for_field($choices),
				'label' => $choices, 
				'value' => self::clean_str_for_field($choices)
			);
		}
		else{
			foreach($choices as $choice){
				if(!is_array($choice)){
					$out[] = array(
						'label' => $choice,
						'id' => $name . '_' . self::clean_str_for_field($choice),
						'value' => self::clean_str_for_field($choice)
					);
				}
				else{
					# if choice is already an array, we need to check for missing data
					if(!array_key_exists('id', $choice)) $choice['id'] = $name.'_'.self::clean_str_for_field($choice['label']);
					if(!array_key_exists('value', $choice)) $choice['value'] = $name.'_'.self::clean_str_for_field($choice['label']);
					## if this choice has children, do a few extra things
					if(array_key_exists('children', $choice)){
						# add a class to indicate this class has children
						$choice['class'] = (isset($choice['class']) ? $choice['class'] . ' has-children' : 'has-children');
						# loop through child fields and make sure we have full arrays for them all
						foreach($choice['children'] as $k => $child_choice){
							$child_choice = self::get_field_array($child_choice);
							$choice['children'][$k] = $child_choice;
						}
					}
					$out[] = $choice;
				}
			}
		}
		return $out;
	} # end: get_choice_array()


	/**
	 * Register all post types and taxonomies
	 * 
	 * Post types to register include:
	 *   - cptd_pt post type
	 *   - cptd_tax post type
	 *   - user-defined post types (cptd_pt posts)
	 *   - user-defined taxonomies (cptd_tax posts)
	 * 
	 * @since 	2.0.0
	 */

	public static function register(){

		# Main CPTD post type
		register_extended_post_type('cptd_pt', 
			array(
				'public' => false,
				'show_ui' => true,
				'menu_icon' => 'dashicons-list-view',
				'menu_position' => '30',
				'labels' => array(
					'menu_name' => 'CPT Directory',
					'all_items' => 'Post Types',
				),
			), 
			array(
				'singular' => 'Post Type',
				'plural' => 'Post Types',
			)
		);

		# CPTD Taxonomies
		register_extended_post_type('cptd_tax',
			array(
				'public' => false,
				'show_ui' => true,
				'show_in_menu' => 'edit.php?post_type=cptd_pt',
				'labels' => array(
					'all_items' => 'Taxonomies'
				)
			),
			array(
				'singular' => 'Taxonomy',
				'plural' => 'Taxonomies',
			)
		);
		
		# User-defined post types
		foreach( CPTD::$post_type_ids as $pt_id){

			$pt = new CPTD_PT( $pt_id );

			# make sure that the post for this post type is published
			if( empty( $pt->post_status ) || 'publish' != $pt->post_status ) continue;

			# register the post type
			$pt->register();
		}

		# User-defined taxonomies
		foreach( CPTD::$taxonomy_ids as $tax_id ){

			$tax = new CPTD_Tax( $tax_id );

			# make sure that the post for this taxonomy is published
			if( empty( $tax->post_status ) || 'publish' != $tax->post_status  ) continue;

			# register the taxonomy
			$tax->register();
		}

	} # end: register()

	/**
	 * Get an alphabetical list of unique field keys for CPTD user-created posts
	 * Fields starting with _ are ignored
	 *
	 * @since 	2.0.0
	 */
	public static function get_all_field_keys() {

		if( is_array( CPTD::$all_field_keys ) ) return CPTD::$all_field_keys;

		# build an array of post type handles
		$pt_names = array();

		# loop through the array of existing CPTD user-created post type ID's to form the array of handles
		foreach( CPTD::$post_type_ids as $pt ) {

			$pt = new CPTD_PT( $pt );

			if(! in_array( $pt->handle, $pt_names ) ) $pt_names[] = $pt->handle;
		}

		# make sure post type handles exist
		if( ! $pt_names ) {

			# indicator that the value has been initialized so we don't have to run this function again
			CPTD::$all_field_keys = array();
			return array();
		}

		global $wpdb;

		# SQL for post meta
		$fields_query = "SELECT DISTINCT meta_key FROM " . $wpdb->postmeta . 
			" WHERE post_id IN (  
				SELECT ID FROM " .  $wpdb->posts .
				" WHERE post_type IN ( '" .
					implode( "', '", $pt_names ) .
				"' )
			) ";

		$fields_results = $wpdb->get_results( $fields_query );

		# make sure we found results
		if( ! $fields_results ) {

			CPTD::$all_field_keys = array();
			return array();
		}

		# the array we'll return 
		$field_keys = array();

		foreach( $fields_results as $r ) {

			# skip fields that start with _
			if( 0 === strpos( $r->meta_key, '_') ) continue;

			$field_keys[] = $r->meta_key;
		}

		CPTD::$all_field_keys = $field_keys;
		return $field_keys;

	} # end: get_all_field_keys()

	/**
	 * Get a list of all core and custom image sizes that are registered
	 *
	 * @since 	2.0.0
	 */
	public static function get_image_sizes() {

		# The WP core image sizes
		$image_sizes = array(
			'thumbnail', 'medium', 'large', 'full'
		);

		# get any custom images sizes that are registered
		global $_wp_additional_image_sizes;
		foreach( $_wp_additional_image_sizes as $size => $info ) {
			$image_sizes[] = $size;
		}

		return $image_sizes;
	} # end: get_image_sizes()

} # end class CPTD_Helper
