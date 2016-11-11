<?php
/**
 * An object for taxonomies created by the plugin
 *
 * Extension of BBD_Post.  Contains data necessary for registering and handling custom taxonomies
 *
 * @since 2.0.0
 */

class BBD_Tax extends BBD_Post{

	/**
	 * The taxonomy name to be registered
	 *
	 * @param 	string
	 * @since 	2.0.0
	 */
	var $handle;

	/**
	 * The singular label for this taxonomy
	 *
	 * @param 	string
	 * @since 	2.0.0
	 */
	var $singular;

	/**
	 * The plural label for this taxonomy
	 *
	 * @param 	string
	 * @since 	2.0.0
	 */
	var $plural;

	/**
	 * A list of the post type ID's for this taxonomy
	 *
	 * @param 	array
	 * @since 	2.0.0
	 */
	var $post_types = array();

	/**
	 * The URL slug for this post type
	 *
	 * @param 	string
	 * @since 	2.0.0
	 */
	var $slug = '';

	/**
	 * An array of WP_Term objects for this taxonomy
	 *
	 * @param 	array
	 * @since 	2.0.0
	 */
	var $terms = array();

	/**
	 * List of object parameters used for taxonomy registration ( $args for register_taxonomy )
	 *
	 * @param 	array
	 * @since 	2.0.0
	 */
	var $args_settings = array( 'public', 'hierarchical' );

	/**
	 * Whether or not this taxonomy is public
	 *
	 * @param 	bool
	 * @since 	2.0.0
	 */
	var $public;

	/**
	 * Whether this taxonomy is hierarchical
	 *
	 * @param 	bool
	 * @since 	2.0.0
	 */
	var $hierarchical;


	/**
	 * Class methods
	 *
	 * - __construct()
	 * - register()
	 */

	/**
	 * Create a new instance
	 *
	 * @param 	(WP_Post|string|int) 		The post or post_id this object extends
	 * @since 	2.0.0
	 */

	public function __construct( $post ){

		parent::__construct( $post );

		if( empty( $this->ID ) ) return;

		$this->load_post_data();
		$this->load_post_meta();

		if( empty( $this->hierarchical ) ) $this->hierarchical = false;
		if( empty( $this->public ) ) $this->public = false;

		# set the slug if the taxonomy is public and doesn't have a slug
		if( $this->public && empty( $this->slug ) && ! empty( $this->post_title ) ) {
			$this->slug = BBD_Helper::clean_str_for_url( $this->post_title );
		}

	} # end: __construct()

	/**
	 * Register the taxonomy associated with this post
	 *
	 * The input array is filtered by `bbd_register_tax` and then passed to `register_extended_taxonomy()`
	 *
	 * variable of interest:
	 *
	 * @type  array 	$args{
	 *		The arguments that will be filtered by `bbd_register_tax`
	 *
	 *   	@type string 		$taxonomy 	 	The taxonomy name to register
	 * 		@type string|array 	$object_type	The post types to associate with this taxonomy
	 *   	@type array 		$args 			The WP $args array for register_taxonomy()
	 * 	 	@type array 		$names 			The $names array for register_extended_taxonomy()
	 * }
	 * @since 2.0.0
	 */

	public function register(){

		# make sure we have a handle set
		if( empty( $this->handle ) ) return;

		# produce an array for the $args['object_type']
		if( empty( $this->post_types ) ) return;

		$object_type = array();
		
		# loop through post ID's which control the post types for this taxonomy
		foreach( $this->post_types as $post_id){

			# make sure we have an acceptable post type
			if( ! in_array( $post_id, BBD::$post_type_ids ) ) return;
			$pt = new BBD_PT( $post_id );

			# add the post type name to the list for registration
			$object_type[] = $pt->handle;

		} # end foreach: post types for this taxonomy

		if( empty( $object_type ) ) return;

		$args = array(
			'taxonomy' => $this->handle,
			'object_type' => $object_type,
			'args' => array(),
			'names' => array(
				'singular' 	=> $this->singular,
				'plural' 	=> $this->plural,
			)
		);

		if( ! empty( $this->slug ) ) $args['names']['slug'] = $this->slug;

		# load in any settings from the backend
		foreach( $this->args_settings as $key ) {
			if( ! empty( $this->$key ) || false === $this->$key ) {
				$value = $this->$key;

				# for checkboxes
				if( 'on' == $value ) {
					$value = true;
				}

				# for integers
				if( 'menu_position' == $key ) $value = intval( $value );
				
				# add to args for register_taxonomy
				$args['args'][ $key ] = $value;

				$this->$key = $value;
			}
		}

		# apply filter that user can hook into
		$args = apply_filters('bbd_register_tax', $args);

		# register the taxonomy using Extended Taxonomies
		register_extended_taxonomy( $args['taxonomy'], $args['object_type'], $args['args'], $args['names'] );

	} # end: register()

	/**
	 * Get an instance by handle or label
	 * Note this does not support taxonomies that may share labels, it returns the first valid match
	 *
	 * @param 	string 		$search_text 	The handle or label for a taxonomy
	 * @return	BBD_Tax
	 * @since 	2.0.0
	 */
	public static function get_by_text( $search_text ) {

		# loop through post IDs for BBD posts
		foreach( BBD::$taxonomy_ids as $id ) {

			$tax = new BBD_Tax( $id );

			# see if we match the handle, labels, or post title
			if( 
				$search_text == $tax->handle || $search_text == $tax->plural || 
				$search_text == $tax->singular || $search_text == $tax->post_title
			) {
				# if the taxonomy is valid, return the object
				if( taxonomy_exists( $tax->handle ) ) {
					return $tax;
				}
			}
		} # end foreach: taxonomy IDs
	} # end: get_by_text

	/**
	 * Generate a terms dropdown for this taxonomy
	 *
	 * @since 	2.0.0
	 */
	public function get_form_element_html( $setting = array(), $option = '' ) {

		# get the terms if necessary
		if( empty( $this->terms ) ) {

			$terms = get_terms( $this->handle );
			if( ! is_wp_error( $terms ) ) $this->terms = $terms;
		}

		if( empty( $this->terms ) ) return;

		# sort by hierarchy if we have a heirarchical taxonomy
		if( $this->hierarchical ) {
			$this->terms = BBD_Helper::sort_terms_by_hierarchy( $this->terms );
		}

		$choices = array();
		$choices[] = array( 'value' => '', 'label' => 'Select' );

		# Loop through terms and load choices for dropdown
		foreach( $this->terms as $term ) {

			# add the term to the choices array
			$choices[] = array( 'value' => $term->term_id, 'label' => $term->name );

			# load any child terms
			if( $this->hierarchical && ! empty( $term->children ) ) {
				foreach( $term->children as $child ) {
					$choices[] = array( 
						'value' => $child->term_id, 

						# note that Chrome does not support padding/margin for <option> elements
						'label' => '&nbsp; &nbsp;' . $child->name, 
						'class' => 'bbd-term-indent' 
					);
				}
			}
		}

		$setting['choices'] = $choices;
		$setting = BBD_Helper::get_field_array( $setting );
		?>
		<label for='<?php echo $setting['id']; ?>' ><?php echo $this->plural; ?></label>
		<?php 
		BBD_Options::do_settings_field( $setting, $option, $_POST );
		
	} # end: get_form_element_html()

} # end class: BBD_Tax
