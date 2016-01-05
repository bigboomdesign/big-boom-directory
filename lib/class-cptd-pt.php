<?php
/**
 * An object for post types created by the plugin
 *
 * Extension of CPTD_Post.  Contains data necessary for registering and handling custom post types
 *
 * @since 2.0.0
 */
class CPTD_PT extends CPTD_Post{
	
	/**
	 * Class parameters
	 */

	/**
	 * The post type name to be registered
	 *
	 * @param 	string
	 * @since 	2.0.0
	 */
	var $handle;

	/**
	 * The singular label for this post type
	 *
	 * @param 	string
	 * @since 	2.0.0
	 */
	var $singular;

	/**
	 * The plural label for this post type
	 *
	 * @param 	string 
	 * @since 	2.0.0
	 */
	var $plural;

	/**
	 * The URL slug for this post type
	 *
	 * @param 	string 
	 * @since 	2.0.0
	 */
	var $slug;

	/**
	 * The default post orderby parameter for this post type
	 * 
	 * @param 	string 	( title | meta_value | date | rand )
	 * @since 	2.0.0
	 */
	var $post_orderby;

	/**
	 * The meta key parameter to use if ordering by custom field
	 *
	 * @param	string
	 * @since	2.0.0
	 */
	var $meta_key_orderby;

	/**
	 * The default post order for this post type
	 *
	 * @param	string 	( ASC | DESC )
	 * @param 	2.0.0
	 */
	var $post_order;

	/**
	 * The ACF field keys chosen for this post type's views (eg: field_12345678)
	 *
	 * @param	array
	 * @since 	2.0.0
	 */
	var $acf_archive_fields = array();
	var $acf_single_fields = array();

	/**
	 * Whether or not to auto detect website fields and social media links
	 *
	 * @param	bool
	 * @since 	2.0.0
	 */
	var $auto_detect_url = false;
	var $auto_detect_social = false;

	/**
	 * 
	 */
	var $url_link_texts = array();

	/**
	 * The image sizes for this post type's views (chosen by user from WP's registered sizes)
	 *
	 * @param 	string
	 * @since	2.0.0
	 */
	var $image_size_single;
	var $image_size_archive;

	/**
	 * The image alignment for the post type's image fields
	 *
	 * @param 	string		(none|left|right)
	 * @since 	2.0.0
	 */
	var $image_alignment;

	/**
	 * List of object parameters used for post registration ($args for register_post_type)
	 * 
	 * @param 	array
	 * @since 	2.0.0
	 */
	var $args_settings = array( 'public', 'has_archive', 'exclude_from_search', 'menu_position', 'menu_icon' );

	/** 
	 * Whether the post type is public
	 * 
	 * @param 	bool
	 * @since 	2.0.0
	 */
	var $public;

	/** 
	 * Whether the post type has an archive page
	 * 
	 * @param 	bool
	 * @since 	2.0.0
	 */
	var $has_archive;

	/**
	 * Whether to exclude the post type from WP search
	 *
	 * @param 	bool
	 * @since 	2.0.0
	 */
	var $exclude_from_search;

	/**
	 * The position of the post type in the WP Admin menu
	 *
	 * @param 	int
	 * @since 	2.0.0
	 */
	var $menu_position;

	/**
	 * The menu icon to use in the WP Admin menu
	 * 
	 * @param 	string 
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

		$args['args']['supports'] = array('title', 'editor', 'excerpt');
		$args['args']['show_in_nav_menus'] = false;

		# add featured image support for all post types if the theme does
		if( current_theme_supports('thumbnail') ) $args['args']['supports'][] = 'thumbnail';

		$args = apply_filters('cptd_register_pt', $args );
		register_extended_post_type($args['post_type'], $args['args'], $args['names']);
	} # end: register()

} # end class: CPTD_PT