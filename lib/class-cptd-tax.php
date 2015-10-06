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
	 * @param 	string		The taxonomy name to be registered
	 * @since 	2.0.0
	 */
	var $handle;

	/**
	 * @param 	string		The singular label for this taxonomy
	 * @since 	2.0.0
	 */
	var $singular;

	/**
	 * @param 	string		The plural label for this taxonomy
	 * @since 	2.0.0
	 */
	var $plural;

	/**
	 * @param 	bool		Whether this taxonomy is hierarchical
	 * @since 	2.0.0
	 */
	var $hierarchical;

	/**
	 * @param 	array		A list of the post type ID's for this taxonomy
	 * @since 	2.0.0
	 */
	var $post_types = array();

	/**
	 * @param 	string		The URL slug for this post type
	 * @since 	2.0.0
	 */
	var $slug;

	/**
	 * @param 	array 		List of object parameters used for taxonomy registration ( $args for register_taxonomy )
	 * @since 	2.0.0
	 */
	var $args_settings = array();

	/**
	 * @param 	bool 		Whether or not this taxonomy is public
	 * @since 	2.0.0
	 */
	var $public;


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

		if( ! $this->ID ) return;

		$this->load_post_data();
		$this->load_post_meta();

		if( empty( $this->hierarchical ) ) $this->hierarchical = false;

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
		if( empty( $this->post_types ) ) return;

		$object_type = array();
		
		# loop through post ID's which control the post types for this taxonomy
		foreach( $this->post_types as $post_id){

			# make sure we have an acceptable post type
			if( ! in_array( $post_id, CPTD::$post_type_ids ) ) return;
			$pt = new CPTD_pt( $post_id );

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


		if( ! empty( $this->slug ) ) $ars['names']['slug'] = $this->slug;
		if( is_bool( $this->hierarchical ) ) {
			$args['args']['hierarchical'] = $this->hierarchical;
		}

		# apply filter that user can hook into
		$args = apply_filters('cptd_register_tax', $args);

		# register the taxonomy using Extended Taxonomies
		register_extended_taxonomy( $args['taxonomy'], $args['object_type'], $args['args'], $args['names'] );

	} # end: register()

} # end class: CPTD_tax