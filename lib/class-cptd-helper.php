<?php
/**
 * Performs helper functions for the plugin's various components
 *
 * @since 	2.0.0
 */
class CPTD_Helper{

	/**
	 * The social media keys that can be auto detected
	 *
	 * @param	array
	 * @since 	2.0.0
	 */
	static $auto_social_field_keys = array(
		'facebook', 
		'twitter',
		'youtube', 'you-tube', 'you_tube',
		'googleplus', 'google_plus', 'google-plus', 'gplus', 'g-plus', 'g_plus',
		'pinterest',
		'instagram',
		'linkedin', 'linked_in', 'linked-in',
	);

	/**
	 * Class Methods
	 *
	 * - make_excerpt()
	 * - get_post_field()
	 * - clean_str_for_url()
	 * - clean_str_for_field()
	 * - get_field_array()
	 * - get_choice_array()
	 *
	 * - register()
	 * - get_all_post_ids()
	 * - get_all_post_ids_for_post_types()
	 * - get_all_post_ids_for_terms()
	 * - get_all_post_ids_for_fields()
	 * - get_all_field_keys()
	 * - get_image_sizes()
	 *
	 * - sort_terms_by_hierarchy()
	 *
	 * - checkboxes_for_post_types()
	 * - checkboxes_for_taxonomies()
	 * - checkboxes_for_terms()
	 * - checkboxes_for_fields()
	 * - draggable_fields()
	 */

	/**
	 * Create an excerpt of a given string with a given length and trailer
	 *
	 * @param 	$content 	The string to truncate
	 * @param 	$length		The number of characters (rounded down to account for full word)
	 * @param 	$after 		The HTML to display after the excerpt
	 *
	 * @return 	string
	 * @since 	2.0.0
	 */
	public static function make_excerpt( $content, $length = 250, $after = '...' ) {

		$excerpt = $content;
		$excerpt = substr( $excerpt, 0, $length );

		# return the original string if we have no difference
		if( $excerpt == $content ) return $excerpt;
		
		# find the cut point (the last space in the string) and make the cut
		$cut_point = strrpos( $excerpt, ' ' );
		if( $cut_point ) $excerpt = trim( substr( $excerpt, 0, $cut_point ) );

		if( '' == $excerpt ) return '';

		# append the HTML from $after to the end of the string 
		$excerpt .= $after;

		# make sure we return a string with balanced HTML tags
		$excerpt = force_balance_tags( $excerpt );

		return $excerpt;
	
	} # end: make_excerpt()
	
	/**
	 * Check if a $_POST value is empty and return sanitized value
	 *
	 * @param 	string 	$field 		The key to check within the $_POST array
	 *
	 * @return 	string
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
	 *
	 * @return 	string
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
	 *
	 * @return 	string
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
				$out['choices'] = $field['choices'];
				$out['choices'] = self::get_choice_array($out);
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
	 *
	 * @return 	array
	 * @since 	2.0.0
	 */
	public static function get_choice_array( $setting ) {
		extract( $setting );
		if( ! isset( $choices ) ) return array();
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
	 * Return an array of post ID's belonging to all user-created custom post types
	 * 
	 * @return 	array
	 * @since 	2.0.0
	 */
	public static function get_all_post_ids() {

		if( is_array( CPTD::$all_post_ids ) ) return CPTD::$all_post_ids;

		# the indicator that we've checked this and don't need to query the DB
		CPTD::$all_post_ids = array();

		$pt_names = CPTD::get_post_type_names();

		if( ! $pt_names ) return array();

		$post_ids = array();

		global $wpdb;
		$post_id_query = "SELECT DISTINCT ID FROM " . $wpdb->posts . 
			" WHERE post_type IN ( '" . 
				implode( "', '", $pt_names ) .
			"' )";
		$post_id_results = $wpdb->get_results( $post_id_query );

		foreach( $post_id_results as $r ) {

			$post_ids[] = $r->ID;
		}

		CPTD::$all_post_ids = $post_ids;

		return $post_ids;

	} # end: get_all_post_ids()

	/**
	 * Return a list of post IDs for a given set of post types
	 *
	 * @param 	array 	$post_types 	A mixed list of post type handles, labels, or IDs
	 *
	 * @return 	array
	 * @since 	2.0.0
	 */
	public static function get_all_post_ids_for_post_types( $post_types ) {

		$cptd_post_type_handles = array();
		$post_ids = array();

		# loop through the given post types and store objects into array
		foreach( $post_types as $post_type ) {
			foreach( CPTD::$post_type_ids as $id ) {

				$pt = new CPTD_PT( $id );
				if( empty( $pt->ID ) ) continue;

				if( in_array( 
					$post_type, 
					array( $pt->handle, $pt->singular, $pt->plural, strval( $pt->ID ) ) 
				) ) {
					$cptd_post_type_handles[] = $pt->handle;
				}
			} # end foreach: CPTD post type IDs
		} # end foreach: given post types

		if( empty( $cptd_post_type_handles ) ) return array();

		# get post IDS based on the post types we found
		global $wpdb;
		$post_ids_query = "SELECT DISTINCT ID FROM " . $wpdb->posts . 
			" WHERE post_type IN ( '" .
				implode( "', '", $cptd_post_type_handles ) .
			"' )";
		$post_ids_result = $wpdb->get_results( $post_ids_query );

		foreach( $post_ids_result as $r ) {
			$post_ids[] = $r->ID;
		}

		return $post_ids;

	} # end: get_all_post_ids_for_post_types()

	/**
	 * Return a list of post IDs for a given set of terms 
	 * If $taxonomy is empty, term IDs must be used
	 * If $taxonomy is non-empty, then a mixture of term names and term IDs can be used
	 *
	 * @param 	array 	$terms		A list of terms as described above
	 * @param 	string 	$taxonomy	A handle or label for the taxonomy to get terms from
	 *
	 * @return 	array
	 * @since 	2.0.0
	 */
	public static function get_all_post_ids_for_terms( $terms, $taxonomy = '' ) {
		
		$term_ids = array();

		# if we don't have a taxonomy, make sure that all $terms are IDs
		if( empty( $taxonomy ) ) {
			foreach( $terms as $term ) {
				if( intval( $term ) ) $term_ids[] = intval( $term );
			}
		}

		# if we do have a taxonomy, try to match term IDs by each given ID or name
		else {

			# make sure we can find a valid taxonomy based on the given value
			$tax = CPTD_Tax::get_by_text( $taxonomy );
			if( empty( $tax->ID ) ) return array();

			# get terms for the taxonomy
			$wp_terms = get_terms( array('taxonomy' => $tax->handle ) );

			# loop through terms and store term IDs into the term ID array
			if( ! is_wp_error( $wp_terms ) ) foreach( $wp_terms as $wp_term ) {
				if( 
					in_array( strval( $wp_term->term_id ), $terms  )
					|| in_array( $wp_term->name, $terms )

				) { 
					$term_ids[] = $wp_term->term_id; 
				} # end if: terms matches a user-submitted term ID or term name
			} // end foreach: terms for given $taxonomy
		} # end else: $taxonomy is set
		
		# make sure we have term IDs
		if( ! $term_ids ) return array();

		# get the post IDs for the terms IDs we found
		$post_ids = array();

		global $wpdb;
		$post_id_query = "SELECT DISTINCT object_id FROM " . $wpdb->term_relationships . 
			" WHERE term_taxonomy_id IN ( '" . 
				implode( "', '", $term_ids ) .
			"' )";
		$post_id_results = $wpdb->get_results( $post_id_query );

		foreach( $post_id_results as $r ) {

			$post_ids[] = $r->object_id;
		}

		return $post_ids;
	} # end: get_all_post_ids_for_terms()

	/**
	 * Return a list of post IDs for a given set of field key/value pairs
	 *
	 * @param 	array 	$fields 		Associative array of key/value pairs of fields to get posts by
	 * @param 	string 	$operation 		Whether to match all given values (use "AND") or any given values (use "OR")
	 *
	 * @return 	array
	 * @since 	2.0.0
	 */
	public static function get_all_post_ids_for_fields( $fields, $operation = 'OR' ) {

		# the post ID array we'll return
		$post_ids = array();

		# the meta_key/meta_value clauses for our db query
		$clauses = array();

		# loop through given fields and populate clauses
		foreach( $fields as $k => $v ) {

			$key = sanitize_key( $k );

			# if we have an array of field values
			if( is_array( $v ) ) {
				foreach( $v as $field_value ) {
					$clauses[] = " ( meta_key='" . $key . 
						"' AND meta_value='" . sanitize_text_field( $field_value ) . 
					"' ) ";
				}
			}

			# if we have a single value
			else {
				$value = sanitize_text_field( $v );
				$clauses[] = " ( meta_key='" . $key . "' AND meta_value='" . $value . "' ) ";
			}
		}

		if( empty( $clauses ) ) return array();

		# query the database for field matches
		global $wpdb;
		$field_match_query = "SELECT DISTINCT meta_key, post_id FROM " . $wpdb->postmeta .
			" WHERE " . implode( " OR ", $clauses );

		$field_match_results = $wpdb->get_results( $field_match_query );

		/**
		 * Store the matching post IDs for each field key 
		 *
		 * @type array {
		 *
		 * 		'field_key_1' => array( id1, id2, ... ),
		 *		'field_key_2' => array( id1, id2, ... ),
		 * 		...
		 * }
		 */
		$post_id_matches = array();

		foreach( $field_match_results as $r ) {
			$post_id_matches[ $r->meta_key ][] = $r->post_id;
		}

		# if we're looking for any field match (OR)
		if( 'OR' == strtoupper( $operation ) ) {
			foreach( $post_id_matches as $k => $ids ) {
				foreach( $ids as $id ) if( ! in_array( $id, $post_ids ) ) $post_ids[] = $id;
			}
		}

		# if we're matching all fields (AND)
		elseif( 'AND' == strtoupper( $operation ) ) {
			$i = 0;
			$intersection = array();
			foreach( $post_id_matches as $k => $ids ) {
				if( 0 == $i ) {
					$intersection = $ids;
				}
				else { $intersection = array_intersect( $intersection, $ids ); }
				$i++;
			}

			$post_ids = $intersection;
		} # end else: matching all fields (AND)

		return $post_ids;
		
	} # end: get_all_post_ids_for_fields()

	/**
	 * Get an alphabetical list of unique field keys for CPTD user-created posts
	 * Fields starting with _ are ignored
	 *
	 * @since 	2.0.0
	 */
	public static function get_all_field_keys() {

		if( is_array( CPTD::$all_field_keys ) ) return CPTD::$all_field_keys;

		# indicator that the value has been initialized so we don't have to run this function again
		CPTD::$all_field_keys = array();

		# if we have no post types, do nothing further
		if( CPTD::$no_post_types ) {
			return array();
		}

		$post_ids = self::get_all_post_ids();

		# make sure post type handles exist 
		if( ! $post_ids ) return array();

		global $wpdb;

		# SQL for post meta
		$fields_query = "SELECT DISTINCT meta_key FROM " . $wpdb->postmeta . 
			" WHERE post_id IN (  " . 
					implode( ", ", $post_ids ) .
			" ) ORDER BY meta_key ASC";

		$fields_results = $wpdb->get_results( $fields_query );

		# make sure we found results
		if( ! $fields_results ) {

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
		if( empty( $wp_additional_image_sizes ) ) return $image_sizes;

		foreach( $_wp_additional_image_sizes as $size => $info ) {
			$image_sizes[] = $size;
		}

		return $image_sizes;
	} # end: get_image_sizes()

	/**
	 * Sort an array of taxonomy terms hierarchically. Child categories will be
	 * placed under a 'children' member of their parent term.
	 *
	 * @param array 	$terms     		List of WP_Term objects
	 * @param int		$parent_id 		The parent ID for the terms
	 */
	public static function sort_terms_by_hierarchy( $terms, $parent_id = 0 ) {
	    
	    $out = array();

	    # load the terms matching the given parent ID
	    foreach ( $terms as $i => $term ) {

	        if ( $term->parent == $parent_id ) {
	            $out[ $term->term_id ] = $term;
	            unset( $terms[ $i ] );
	        }
	    }

	    # recurse back into the function for all the top level terms we found
	    foreach ($out as &$top_level_term ) {
	        $top_level_term->children = self::sort_terms_by_hierarchy( $terms, $top_level_term->term_id );
	    }

	    return $out;

	} # end: sort_terms_by_hierarchy()

	/**
	 * Generate HTML to display checkboxes for post types registered by the plugin
	 *
	 * @param 	array 	$args 		{
	 * 		
	 * 		The arguments for the checkbox group (none required)
	 *
	 * 		@type 	array 	$selected 		The post type post IDs to be pre-selected
	 * 		@type	string 	$heading		HTML for heading to be displayed above the checkboxes
	 * 		@type 	string 	$description	HTML for description to be displayed above the checkboxes
	 * 		@type 	string	$field_id		The id attribute for individual checkboxes
	 * 		@type 	string	$field_name		The name attribute for individual checkboxes (we add [] to store as an array)
	 * 		@type 	string 	$label_class	The class to attach to each checkbox label
	 * }
	 * @return 	string
	 * @since 	2.0.0
	 */
	public static function checkboxes_for_post_types( $args = array() ) {
		
		if( empty( CPTD::$post_types ) ) return '';

		ob_start();
		
		# get arguments
		$defaults = array(
			'selected' => array(),
			'heading' => '',
			'description' => '',
			'field_id' => '',
			'field_name' => '',
			'label_class' => '',
		);
		$args = wp_parse_args( $args, $defaults );

		# heading and description
		if( ! empty( $args['heading'] ) ) echo $args['heading'];
		if( ! empty( $args['description'] ) ) echo $args['description'];

		# loop through post types and display checkboxes
		foreach( CPTD::$post_types as $post_type ) {

			$pt = new CPTD_PT( $post_type->ID );
		?>
			<label for="<?php echo $args['field_id'] . '_'  . $pt->ID ; ?>" class="<?php echo $args['label_class']; ?>">
				<input id="<?php echo $args['field_id'] . '_'  . $pt->ID; ?>"
					type='checkbox'
					name="<?php echo $args['field_name']; ?>[]"
					value="<?php echo $pt->ID; ?>"
					<?php checked( true, in_array( $pt->ID, $args['selected'] ) ); ?>
				/> <?php echo $pt->plural; ?>
			</label>
		<?php
		} # end foreach: registered post types

		$html = ob_get_contents();
		ob_end_clean();
		return $html;

	} # end: checkboxes_for_post_types()

	/**
	 * Generate HTML to display checkboxes for taxonomies registered by the plugin
	 *
	 * @param 	array 	$args 		{
	 * 		
	 * 		The arguments for the checkbox group (none required)
	 *
	 * 		@type 	array 	$selected 		The taxonomy post IDs to be pre-selected
	 * 		@type	string 	$heading		HTML for heading to be displayed above the checkboxes
	 * 		@type 	string 	$description	HTML for description to be displayed above the checkboxes
	 * 		@type 	string	$field_id		The id attribute for individual checkboxes
	 * 		@type 	string	$field_name		The name attribute for individual checkboxes (we add [] to store as an array)
	 * 		@type 	string 	$label_class	The class to attach to each checkbox label
	 * }
	 * @return 	string
	 * @since 	2.0.0
	 */
	public static function checkboxes_for_taxonomies( $args = array() ) {
		
		if( empty( CPTD::$taxonomies ) ) return '';
		
		ob_start();

		# get arguments
		$defaults = array(
			'selected' => array(),
			'field_id' => '',
			'field_name' => '',
			'label_class' => '',
		);
		$args = wp_parse_args( $args, $defaults );

		# loop through taxonomies and display checkboxes
		foreach( CPTD::$taxonomies as $taxonomy ) {

			$tax = new CPTD_Tax( $taxonomy->ID );
		?>
			<label for="<?php echo $args['field_id'] . '_'  . $tax->ID ; ?>" class="<?php echo $args['label_class']; ?>">
				<input id="<?php echo $args['field_id']. '_'  . $tax->ID; ?>"
					type='checkbox'
					name="<?php echo $args['field_name']; ?>[]"
					value="<?php echo $tax->ID; ?>"
					<?php checked( true, in_array( $tax->ID, $args['selected'] ) ); ?>
				/> <?php echo $tax->plural; ?>
			</label>
		<?php
		} # end foreach: registered taxonomies

		$html = ob_get_contents();
		ob_end_clean();
		return $html;

	} # end: checkboxes_for_taxonomies()

	/**
	 * Generate HTML for terms checkboxes for a given taxonomy
	 *
	 * @param 	array 	$args 		{
	 * 		
	 * 		The arguments for the checkbox group (none required)
	 *
	 * 		@type 	array 	$selected 		The term IDs to be pre-selected
	 * 		@type	string 	$heading		HTML for heading to be displayed above the checkboxes
	 * 		@type 	string 	$description	HTML for description to be displayed above the checkboxes
	 * 		@type 	string	$field_id		The id attribute for individual checkboxes
	 * 		@type 	string	$field_name		The name attribute for individual checkboxes (we add [] to store as an array)
	 * 		@type 	string 	$label_class	The class to attach to each checkbox label
	 * }
	 * @param 	int 	$tax_id 	The post ID for the CPTD taxonomy
	 * @return 	string
	 * @since 	2.0.0
	 */
	public static function checkboxes_for_terms( $args, $tax_id ) {
		
		# get the taxonomy
		$tax = new CPTD_Tax( $tax_id );
		if( ! $tax->ID ) return '';

		# get the terms for the taxonomy
		$terms_query_args = array(
			'taxonomy' => $tax->handle,
		);

		$terms = get_terms( $terms_query_args );

		if( ! $terms || is_wp_error( $terms ) ) return '';

		# get arguments
		$defaults = array(
			'selected' => array(),
			'field_id' => '',
			'field_name' => '',
			'label_class' => '',
		);
		$args = wp_parse_args( $args, $defaults );

		# generate the output
		ob_start();

		# loop through terms and display a checkbox for each one
		foreach( $terms as $term ) {
		?>
			<label for="<?php echo $args['field_id'] . '_'  . $term->term_id; ?>" class="<?php echo $args['label_class']; ?>">
				<input id="<?php echo $args['field_id']. '_'  . $term->term_id; ?>"
					type='checkbox'
					name="<?php echo $args['field_name']; ?>[]"
					value="<?php echo $term->term_id; ?>"
					<?php checked( true, in_array( $term->term_id, $args['selected'] ) ); ?>
				/> <?php echo $term->name; ?>
			</label>
		<?php
		} # end foreach: $terms

		$html = ob_get_contents();
		ob_end_clean();
		return $html;

	} # checkboxes_for_terms()

	/**
	 * Generate HTML for ACF field group checkboxes and dynamic field checkboxes for each group
	 *
	 * @param 	array 	$args 		{
	 * 		
	 * 		The arguments for the checkbox group (none required)
	 *
	 * 		@type 	array 	$selected 		The field group IDs to be pre-selected
	 * 		@type 	string	$field_id		The id attribute for individual checkboxes
	 * 		@type 	string	$field_name		The name attribute for individual checkboxes (we add [] to store as an array)
	 * 		@type 	string 	$label_class	The class to attach to each checkbox label
	 * 		@type 	string 	$field_class	THe class to add to each field container
	 * }
	 * @return 	string
	 * @since 	2.0.0
	 */
	public static function checkboxes_for_fields( $args ) {

		# get arguments
		$defaults = array(
			'selected' => array(),
			'field_id' => '',
			'field_name' => '',
			'label_class' => '',
			'field_class' => '',
		);
		$args = wp_parse_args( $args, $defaults );
		?>
		<!-- Show/Hide Fields link -->
		<a data-field-id='<?php echo $args['field_id']; ?>' class='show-hide-fields-area'>Show Fields</a>
		<div class='cptd-fields-area'>
		<?php

			# loop through custom fields and display checkboxes and options area for each field
			foreach( self::get_all_field_keys() as $field ) {

				$field = new CPTD_Field( $field );
			?>
			<div class='<?php echo $args['field_class']; ?>'>

				<?php # The main field checkbox ?>
				<label for="<?php echo $args['field_id'] . '[' . $field->key . ']'; ?>">
					<input type="checkbox" name="<?php echo $args['field_name'] . '[]'; ?>" 
						id="<?php echo $args['field_id'] . '[' . $field->key . ']'; ?>" 
						value="<?php echo $field->key; ?>" 
						<?php
							# check the checkbox if necessary
							if( ! empty( $args['selected'] ) && is_array( $args['selected'] ) ) 
							foreach ($args['selected'] as $f ) { 
								checked( $f , $field->key );  
							}
						?>
					/><?php echo $field->label; ?>
				</label>
				<?php 
				# execute an action after each checkbox that we can hook into for different purposes
				# for example, to show filter options in the search widget
				do_action( 'cptd_after_field_checkbox', $field ); ?>

			</div><!-- .{field_class}  -->
			<?php

			} # end foreach: $widget->field_keys
		?>
		</div><!-- .cptd-fields-area -->
		<?php

	} # end: checkboxes_for_fields()

	public static function draggable_fields( $args ) {

		# get arguments
		$defaults = array(
			'selected' => array(),
			'field_id' => '',
			'field_name' => '',
			'label_class' => '',
			'field_class' => '',
		);
		$args = wp_parse_args( $args, $defaults );
		?>
		<div class='cptd-draggable-fields-container' >

		<!-- Show/Hide Fields link -->
		<a data-field-id='<?php echo $args['field_id']; ?>' class='show-hide-fields-area'>Show Fields</a>
		
		<!-- Droppable area for the fields -->
		<div class='cptd-fields-drop'><span class='placeholder-text'>Drop fields here</span>
			<?php

			# Add any saved fields to the droppable area
			if( ! empty( $args['selected'] ) ) {

				# keeps track of fields completed, to prevent any duplicates
				$fields_done = array();

				foreach( $args['selected'] as $key ) {

					# check for duplicates
					if( in_array( $key, $fields_done ) ) {
						continue;
					}
					$fields_done[] = $key;

					# get the field object
					$field = new CPTD_Field( $key );
					?>
					<div class='<?php echo $args['field_class']; ?>'>

						<?php # The main field checkbox ?>
						<label 
							data-field-name='<?php echo $args['field_name']; ?>' 
							data-field-key='<?php echo $field->key; ?>' 
							for="<?php echo $args['field_id'] . '[' . $field->key . ']'; ?>"
						><?php
							echo $field->label;
						?>
						</label>
						<div class='dashicons dashicons-no-alt cptd-remove-field'></div>
						<input type="hidden" name="<?php echo $args['field_name']; ?>[]" value="<?php echo $field->key; ?>"/>
					</div><!-- .{field_class}  -->
					<?php

				} # end foreach: selected keys
			
			} # end if: $args['selected'] not empty
			?>
			<div id='droppable-helper-<?php echo $args['field_id']; ?>'
				data-field-name='<?php echo $args['field_name']; ?>' class='cptd-droppable-helper'>
			</div>
		</div>
		<div class='cptd-fields-area'>
		<?php

			# loop through custom fields and display checkboxes and options area for each field
			foreach( self::get_all_field_keys() as $field ) {

				$field = new CPTD_Field( $field );
			?>
			<div class='<?php echo $args['field_class']; ?>'>

				<?php # The main field checkbox ?>
				<label 
					data-field-name='<?php echo $args['field_name']; ?>' 
					data-field-key='<?php echo $field->key; ?>' 
					for="<?php echo $args['field_id'] . '[' . $field->key . ']'; ?>"
				><?php
					echo $field->label;
				?></label>
			</div><!-- .{field_class}  -->
			<?php

			} # end foreach: $widget->field_keys
		?>
		</div><!-- .cptd-fields-area -->
		</div><!-- .cptd-draggable-fields-container -->
		<?php
	} # end: draggable_fields()

} # end class CPTD_Helper
