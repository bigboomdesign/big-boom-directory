<?php
/**
 * An object for post types created by the plugin
 *
 * Extension of CPTD_Post.  Contains data necessary for registering and handling custom post types
 *
 * @since 2.0.0
 */
class CPTD_pt extends CPTD_Post{
	
	/**
	 * Class parameters
	 */

	/**
	 * @param 	string	$handle 	The post type name to be registered
	 * @since 	2.0.0
	 */
	var $handle;

	/**
	 * @param 	string	$singular 	The singular label for this post type
	 * @since 	2.0.0
	 */
	var $singular;

	/**
	 * @param 	string 	$plural 	The plural label for this post type
	 * @since 	2.0.0
	 */
	var $plural;

	/**
	 * @param 	string 	$slug 		The URL slug for this post type
	 * @since 	2.0.0
	 */
	var $slug;

	/**
	 * @param 	array 	@args_settings 	List of object parameters used for post registration ( $args for register_post_type )
	 */
	var $args_settings = array( 'public', 'has_archive', 'menu_position', 'menu_icon' );

	/** 
	 * @param 	bool 	$public 	Whether the post type is public
	 * @since 	2.0.0
	 */
	var $public;

	/** 
	 * @param 	bool 	$has_archive	Whether the post type has an archive page
	 * @since 	2.0.0
	 */
	var $has_archive;

	/**
	 * @param 	int 	$menu_position 	The position of the post type in the WP Admin menu
	 * @since 	2.0.0
	 */
	var $menu_position;

	/**
	 * @param 	string 	$menu_icon 		The menu icon to use in the WP Admin menu
	 * @since 	2.0.0
	 */
	var $menu_icon;


	/**
	 * Class methods
	 * 
	 * - __construct()
	 * - register()
	 */

	/**
	 * Create a new instance
	 * 
	 * @param 	(WP_Post|string|int) 	The post or post_id this object is extending
	 * @since 	2.0.0
	 */

	public function __construct( $post ){

		parent::__construct($post);

		if( ! $this->ID ) return;

		$this->load_post_data();
		$this->load_post_meta();

	} # end: __construct()

	/**
	 * Register the post type associated with this post
	 *
	 * The input array is filtered by `cptd_register_pt` and then passed to `register_extended_post_type()`
	 *
	 * variable of interest:
	 *
	 * @type  array 	$args{
	 *		The arguments that will be filtered by `cptd_register_pt`
	 *
	 *   	@type string 	$post_type 	 	The post type name to register
	 *   	@type array 	$args 			The WP $args array for register_post_type()
	 * 	 	@type array 	$names 			The $names array for register_extended_post_type
	 * }
	 * @return  null
	 * @since 	2.0.0
	 */

	public function register(){

		# make sure we have the handle set
		if( empty( $this->handle ) ) return;
		
		$args = array(
			'post_type' => $this->handle,
			'args' 		=> array(

			),
			'names' 	=> array(
				'singular' => $this->singular,
				'plural' => $this->plural,
			)
		);

		if( ! empty( $this->slug ) ) $args['names']['slug'] = $this->slug;

		# load in any settings from the backend
		foreach( $this->args_settings as $key ) {
			if( ! empty( $this->$key ) ) {
				$value = $this->$key;

				# for checkboxes
				if( 'on' == $value ) {
					$value = true;
				}

				# for integers
				if( 'menu_position' == $key ) $value = intval( $value );
				$args['args'][ $key ] = $value;

				$this->$key = $value;
			}
		}

		$args = apply_filters('cptd_register_pt', $args );
		register_extended_post_type($args['post_type'], $args['args'], $args['names']);
	} # end: register()

} # end class: CPTD_pt