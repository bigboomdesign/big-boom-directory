<?php
/**
 * Performs helper functions for the plugin's various components
 */
class CPTD_Helper{
	
	/**
	 * Check if a $_POST value is empty and return sanitized value
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
	 * - cptd_pt post type
	 * - cptd_tax post type
	 * - user-defined post types (cptd_pt posts)
	 * - user-defined taxonomies (cptd_tax posts)
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

			$pt = new CPTD_pt( $pt_id );

			# make sure that the post for this post type is published
			if( empty( $pt->post_status ) || 'publish' != $pt->post_status ) continue;

			# register the post type
			$pt->register();
		}

		# User-defined taxonomies
		foreach( CPTD::$taxonomy_ids as $tax_id ){

			$tax = new CPTD_tax( $tax_id );

			# make sure that the post for this taxonomy is published
			if( empty( $tax->post_status ) || 'publish' != $tax->post_status  ) continue;

			# register the taxonomy
			$tax->register();
		}
	} # end: register()

	/**
	 * Admin helper functions
	 * 
	 * - Meta boxes for `cptd_pt` and `cptd_tax` posts
	 * @deprecated
	 */

	/**
	 * Meta boxes for `cptd_pt` and `cptd_tax` posts
	 * 
	 * add_meta_boxes()
	 * post_type_meta_box()
	 * taxonomy_meta_box()
	 * post_content_box()
	 * save_meta_box_data() 
	 * post_edit_admin_notices()
	 *
	 * @deprecated
	 */

	


	/**
	 * Display the "Post Type Settings" meta box for `cptd_pt` posts
	 * 
	 * @param  WP_Post 	$post 	The post we're currently editing
	 * @deprecated
	 */

	public static function taxonomy_meta_box($post){

		$tax = new CPTD_tax($post);
		$tax->load_cptd_meta();
	
		// Add a nonce field so we can check for it later.
		wp_nonce_field( 'cptd_save_meta_box_data', 'cptd_meta_box_nonce' );
		
		ob_start();
		?>
		<div id='taxonomy-meta-box' class='cptd-post-meta-box'>
			<div class='field' id='handle-container'>
				<label for="handle">
					Name <span class='required'>*</span><br />
					<input 
						autocomplete='off' 
						type='text' 
						class='regular-text' 
						id='handle'
						name='cptd_post_meta[handle]'  
						value="<?php echo $tax->meta['handle']; ?>" 
						readonly="readonly"
					/>
					<div>
						<a id='change-name'>Change</a>
						<div style='display: none;' id='cancel-name-change'>
							 | <a>Cancel</a>
							 | <a target='_blank' href='https://codex.wordpress.org/Taxonomies#Registering_a_taxonomy'>More Info</a>
						</div>
					</div>
				</label>
				<div id='handle-info' style='display: none;'>
					<p class="description">The Taxonomy Name is the most important part of your taxonomy. Once it is set and you have assigned posts to your taxonomy, this value should not be changed.</p>
					<p class="description">We guessed the ideal Taxonomy Name based on your title.  If you edit this field, please use only lowercase letters and underscores, and use a singular name like <code>genre</code> instead of a plural name like <code>genres</code>.</p>
				</div>
			</div>
			<div class='field'>
				<label for="singular">
				Singular Label<br />
				<input type='text' name="cptd_post_meta[singular]" id='singular' class='regular-text' value="<?php echo $tax->meta['singular']; ?>" />
				</label>
				<p class='description'>Ex: <code>Genre</code></p>
			</div>
			<div class='field'>
				<label for="plural">
				Plural Label<br />
				<input type='text' class='regular-text' name='cptd_post_meta[plural]' id='plural' value="<?php echo $tax->meta['plural']; ?>"  />
				</label>
				<p class='description'>Ex: <code>Genres</code></p>
			</div>
			<?php
			# Checkboxes for which post types to assign this taxonomy to
			$post_types = CPTD::get_post_types();
			?>
			<div id='tax-assign-pt' class='field'>
				<h3>Post Types</h3>
				<?php
				if(!$post_types){
				?>
					<p>You don't have any post types yet.  You'll need to create a post type before creating taxonomies.</p>
				<?php
				}
				else{
					foreach($post_types as $pt){
					?>
						<label for="pt-<?php echo $pt->ID; ?>">
							<input 
								id="pt-<?php echo $pt->ID; ?>" 
								type='checkbox' 
								name="cptd_tax_meta[post_types][]" 
								value="<?php echo $pt->ID; ?>"
								<?php checked(true, in_array($pt->ID, $tax->tax_meta['post_types']) ); ?>
							/>
								<?php echo $pt->post->post_title; ?>
						</label>
					<?php
					}
				}
				?>
				<div id='tax-assign-tip'>
					<p class='description'>It's usually best to assign only <b>one post type per taxonomy</b>. Otherwise, the terms you create will appear under all post types checked.</p>
					<p class='description'>For example, if you had <code>Books</code> and <code>Movies</code> as post types and <code>Genres</code> as a single taxonomy for both post types, you may end up with the term "Non-Fiction" as an option for both Books and Movies.  In this case, it would be best to create two taxonomies: <code>Book Genres</code> and <code>Movie Genres</code></p>
				</div>
			</div>
		</div><!-- #taxonomy-meta-box -->
		<?php
		$html = ob_get_contents();
		ob_end_clean();
		echo $html;
	} # end: taxonomy_meta_box()
	
	/**
	 * Produce the post type description meta box
	 *
	 * @param  WP_Post $post 	The post we're currently editing
	 * @deprecated
	 */
	public static function post_content_box( $post ) {
		do_action('edit_form_advanced', $post);
		#wp_editor($post->post_content, 'editpost');
	}
	
	/**
	 * Save the post meta in our custom meta boxes when post is saved
	 *
	 * @param  int 	$post_id 	The ID of the post we're editing
	 * @deprecated
	 */

	public static function save_meta_box_data( $post_id ) {

		# Make sure our nonce is set.
		if (!isset( $_POST['cptd_meta_box_nonce'])) 
			return;

		# Verify that the nonce is valid.
		if (!wp_verify_nonce( $_POST['cptd_meta_box_nonce'], 'cptd_save_meta_box_data' ) )
			return;

		# If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if (defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE)
			return;

		# Make sure the user can edit this post
		if (!current_user_can( 'edit_post', $post_id)) return;

		/* OK, it's safe for us to save the data now. */

		# Make sure that required fields are not empty
		if(empty( $_POST['cptd_post_meta']['handle'])) {
			add_settings_error(
				'missing-handle',
				'missing-handle',
				'You have not specified a Name.',
				'error'
			);

			set_transient( 'settings_errors', get_settings_errors(), 30 );
			return false;
		}
		
		// Sanitize user input.
		$cptd_post_meta = $_POST['cptd_post_meta'];
		foreach($cptd_post_meta as $k => &$value){
			$value = sanitize_text_field($value);
		}
		
		// Update the meta field
		update_post_meta($post_id, 'cptd_post_meta', $cptd_post_meta);

		# for `cptd_tax` posts
		if(isset($_POST['cptd_tax_meta'])){
			# the meta value that we'll set 
			$cptd_tax_meta = array();

			# Get the post type ID's checked for the taxonomy
			if(isset($_POST['cptd_tax_meta']['post_types'])){

				$post_type_ids = $_POST['cptd_tax_meta']['post_types'];

				# the post types to save
				$cptd_tax_meta['post_types'] = array();

				foreach($post_type_ids as $id){

					# make sure we have a valid post type
					$pt = new CPTD_pt($id);
					# if so, add it to the meta value array to be saved
					if($pt->ID) $cptd_tax_meta['post_types'][] = $pt->ID;

				}
			}

			update_post_meta($post_id, 'cptd_tax_meta', $cptd_tax_meta);

		} # endif: `cptd_tax` post type

	} # end: save_meta_box_data()

	/**
	 * Process any notices for the post edit screen
	 * @deprecated
	 */ 

	public static function post_edit_admin_notices(){
		// If there are no errors, then we'll exit the function
		if ( ! ( $errors = get_transient( 'settings_errors' ) ) ) {
			return;
		}
		// Otherwise, build the list of errors that exist in the settings errors
		$message = '<div id="cptd-post-edit-message" class="error notice"><ul>';
		foreach ( $errors as $error ) {
		$message .= '<li>' . $error['message'] . '</li>';
		}
		$message .= '</ul></div><!-- #error -->';
		// Write them out to the screen
		echo $message;
		
		// Clear and the transient and unhook any other notices so we don't see duplicate messages
		delete_transient( 'settings_errors' );
		remove_action( 'admin_notices', array('CPTD_Helper', 'post_edit_admin_notices'));
	} # end: post_edit_admin_notices()
} # end class CPTD_Helper