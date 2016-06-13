<?php
/**
 * An object for post types created by the plugin
 *
 * Extension of BBD_Post.  Contains data necessary for registering and handling custom post types
 *
 * @since 2.0.0
 */
class BBD_PT extends BBD_Post{
	
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
	 * Whether to show the UI for post editing for this post type
	 *
	 * @param 	(bool)
	 * @since 	2.1.0
	 */
	var $show_ui;

	/**
	 * Whether (and possibly where) the post type should be shown in the WP Admin menu
	 *
	 * @param 	(str) 		Possible values: inherit, show, hide
	 * @since 	2.1.0
	 */
	var $show_in_menu;

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

		# set the slug if the post type is public and doesn't have a slug
		if( $this->public && empty( $this->slug ) && ! empty( $this->post_title ) ) {
			$this->slug = BBD_Helper::clean_str_for_url( $this->post_title );
		}

		/**
		 * The `show_in_menu` value may need to be set
		 *
		 * If not null, the pt register method will assert the value.  Otherwise, we leave the value
		 * empty and let the default kick in based on `public` and `show_ui`
		 */
		if( 'show' == $this->show_in_menu ) {
			$this->show_ui = true;
			$this->show_in_menu = true;
		}
		elseif( 'hide' == $this->show_in_menu ) {
			$this->show_in_menu = false;
		}
		elseif( 'inherit' == $this->show_in_menu ) {
			$this->show_in_menu = null;
		}
 
	} # end: __construct()

	/**
	 * Register the post type associated with this post
	 *
	 * The input array is filtered by `bbd_register_pt` and then passed to `register_extended_post_type()`
	 *
	 * variable of interest:
	 *
	 * @type  array 	$args{
	 *		The arguments that will be filtered by `bbd_register_pt`
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
		if( empty( $this->handle ) && empty( $this->post_title ) ) return;
		if( empty( $this->handle ) ) $this->handle = BBD_Helper::clean_str_for_field( $this->post_title );
		
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

		# show_ui
		if( $this->show_ui ) $args['args']['show_ui'] = true;

		# show_in_menu
		if( null !== $this->show_in_menu ) $args['args']['show_in_menu'] = $this->show_in_menu;

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
			}

			# for empty values, we need to set the parameter to false
			else {
				$value = false;
			}

			# set the object parameter and add the argument into the array to be registered
			$this->$key = $value;
			$args['args'][ $key ] = $value;
		}

		$args['args']['supports'] = array('title', 'editor', 'excerpt');

		# add featured image support for all post types if the theme does
		if( current_theme_supports('post-thumbnails') ) $args['args']['supports'][] = 'thumbnail';

		$args = apply_filters('bbd_register_pt', $args );
		register_extended_post_type($args['post_type'], $args['args'], $args['names']);
		
	} # end: register()

	/**
	 * Get an instance by handle or label
	 * Note this does not support post types that may share labels, it returns the first valid match
	 *
	 * @param 	string 		$search_text 	The handle or label for a post type
	 *
	 * @return	BBD_PT
	 * @since 	2.2.0
	 */
	public static function get_by_text( $search_text ) {

		# loop through post IDs for BBD posts
		foreach( BBD::$post_type_ids as $id ) {

			$pt = new BBD_PT( $id );

			# see if we match the handle, labels, or post title
			if( 
				$search_text == $pt->handle || $search_text == $pt->plural || 
				$search_text == $pt->singular || $search_text == $pt->post_title
			) {
				# if the post type is valid, return the object
				if( post_type_exists( $pt->handle ) ) {
					return $pt;
				}
			}
		} # end foreach: post type IDs

	} # end: get_by_text

} # end class: BBD_PT