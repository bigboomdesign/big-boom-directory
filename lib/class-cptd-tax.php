<?php
/**
 * An object for taxonomies created by the plugin
 *
 * Extension of CPTD_Post.  Contains data necessary for registering and handling custom taxonomies
 *
 * @since 2.0.0
 */

class CPTD_tax extends CPTD_Post{

	/**
	 * Class parameters
	 */

	/**
	 * @param 	array 	$meta 	Contains the `_cptd_meta_` fields and their values as $k => $v
	 * @since 	2.0.0
	 */
	var $meta = array(); 

	/**
	 *
	 * @param 	array 	$tax_meta 	To be deprecated
	 * @since 	2.0.0
	 */
	var $tax_meta = array(); // from the `cptd_tax_meta` custom field

	/**
	 * @param 	string 	$name 		To be deprecated
	 * @since 	2.0.0
	 */
	var $name;

	/**
	 * @param 	string	$handle 	The taxonomy name to be registered
	 * @since 	2.0.0
	 */
	var $handle;

	/**
	 * @param 	string	$singular 	The singular label for this taxonomy
	 * @since 	2.0.0
	 */
	var $singular;

	/**
	 * @param 	string	$plural	 	The plural label for this taxonomy
	 * @since 	2.0.0
	 */
	var $plural;

	/**
	 * @param 	string 	$slug 		The URL slug for this post type
	 * @since 	2.0.0
	 */
	var $slug;


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
		parent::__construct($post);

		# Load the CPTD post meta
		$this->load_cptd_meta();
		# Load the CPTD tax meta
		$this->get_meta();

		# Set object parameters
		/*
		$this->name = $this->meta['handle'];
		$this->singular = $this->meta['singular'];
		$this->plural = $this->meta['plural'];
		*/

	} # end: __construct()

	/**
	 * Register the taxonomy associated with this post
	 *
	 * The input array is filtered by `cptd_register_tax` and then passed to `register_extended_taxonomy()`
	 *
	 * variable of interest:
	 *
	 * @type  array 	$args{
	 *		The arguments that will be filtered by `cptd_register_tax`
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
		if(!$this->tax_meta['post_types']) return;

		$object_type = array();
		
		# loop through post ID's which control the post types for this taxonomy
		foreach($this->tax_meta['post_types'] as $post_id){

			# make sure we have an acceptable post type
			$pt = new CPTD_pt($post_id);
			if( ! $pt->ID ) continue;

			# add the post type name to the list
			$object_type[] = $pt->name;

		} # end foreach: post types for this taxonomy

		if( ! $object_type ) return;

		$args = array(
			'taxonomy' => $this->name,
			'object_type' => $object_type,
			'args' => array(),
			'names' => array(
				'singular' 	=> $this->singular,
				'plural' 	=> $this->plural
			)
		);

		# apply filter that user can hook into
		$args = apply_filters('cptd_register_tax', $args);

		# register the taxonomy using Extended Taxonomies
		register_extended_taxonomy($args['taxonomy'], $args['object_type'], $args['args'], $args['names']);
	} # end: register()

	/**
	 * Load the CPTD taxonomy meta for this post
	 *
	 * To be deprecated in favor of load_cptd_meta
	 */

	public function get_meta(){

		# get the array from the custom field `cptd_post_meta`
		$meta = get_post_meta($this->ID, 'cptd_tax_meta', true);

		if(!$meta) $meta = array();

		# don't pass any empty values into our array merge if we're forcing the defaults
		foreach($meta as $k => $v){
			if(!$v) unset($meta[$k]);
		}

		$this->tax_meta = shortcode_atts(
			array(
				'post_types' => array(),
			),
			$meta,
			'cptd_tax_meta'
		);
	}

} # end class: CPTD_tax