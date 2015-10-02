<?php
/**
 * Stores static information about post types and taxonomies created by CPTD
 * Handles callbacks for front end WP actions and filters
 * Handles callbacks for shortcodes
 * Handles callbacks for custom front end actions and filters
 *
 * @since 2.0.0
 */
class CPTD{

	# List of post types (WP_Post objects) created by CPTD
	public static $post_types = array();

	# whether we've tried loading post types and found none (to prevent querying again)
	public static $no_post_types = false;

	# List of taxonomies (WP_Post objects) created by CPTD
	public static $taxonomies = array();

	# whether we've tried loading taxonomies and found none (to prevent querying again)
	public static $no_taxonomies = false;

	# whether we're viewing a CPTD object on the front end
	public static $is_cptd = null;

	# the post type for the current view
	public static $current_post_type = '';

	
	/**
	 * Basic WP callbacks for actions and filters
	 *
	 * - pre_get_posts()
	 */

	/**
	 * Callback for 'pre_get_posts' action
	 *
	 * Defines a custom action 'cptd_pre_get_posts' after validating the view as CPTD
	 *
	 * @since 2.0.0
	 */
	public static function pre_get_posts($query) {

		# make sure we have the main query
		if( ! $query->is_main_query() ) return;

		# make sure we're viewing a CPTD object
		if( ! is_cptd_view() ) return;
		
		# action that users can hook into
		do_action('cptd_pre_get_posts', $query);
	} # end: pre_get_posts()

	/**
	 * Retrieve and store static information about post types and taxonomies
	 *
	 * - load_view_info()
	 * - get_post_types()
	 * - get_taxonomies()
	 */

	/**
	 * Load info about the current front end view
	 *
	 * Initializes the following static variables
	 *
	 * - self::$is_cptd
	 * - self::$view_type
	 * - self::$current_post_type
	 * - self::$current_taxonomy
	 * 
	 * @since 	2.0.0
	 */
	public static function load_view_info() {

		# reduce weight for pages, posts, categories, and tags
		if( is_page() || is_single() || is_category() || is_tag() ) {
			CPTD::$is_cptd = false;
			return;
		}

		global $wp_query;
		if( empty( $wp_query ) ) return;

		# check the queried post type
		if( ! empty( $wp_query->query_vars['post_type'] ) ) {

			$queried_post_type = $wp_query->query_vars['post_type'];
			
			# set the current post type
			CPTD::$current_post_type = $queried_post_type;

			# get CPTD post types
			$post_types = CPTD::get_post_types();

			# loop through post types and check against queried post type
			foreach( $post_types as $pt) {
				if( $queried_post_type == $pt->name ) {
					CPTD::$is_cptd = true;
				}
			}

		} # end if: $wp_query has post type set

		# check the queried taxonomy
		if( ! empty( $wp_query->tax_query->taxonomy ) ) {

			$queried_taxonomy = $wp_query->tax_query->taxonomy;

			# set the current taxonomy
			CPTD::$current_taxonomy = $queried_taxonomy;
			
			# get CPTD taxonomies
			$taxonomies = CPTD::get_taxonomies();

			# loop through taxonomies and check against queried taxonomy
			foreach( $taxonomies as $tax ) {
				if( $queried_taxonomy == $tax->name ) {
					CPTD::$is_cptd = true;
				}
			}

			# loop through taxonomies and check against queried taxonomy
		} # end if: $wp_query has taxonomy set

	} # end: load_view_info()

	/**
	 * Return and/or populate the self::$post_types array
	 *
	 * @since 	2.0.0
	 * @return 	array 	May be empty.
	 * 
	 */
	public static function get_post_types() {

		# see if the post types are already loaded
		if( self::$post_types ) return self::$post_types;
		elseif(self::$no_post_types) return array();
		
		# query for the cptd_pt post type
		$post_types = get_posts(array(
			'post_type' 		=> 'cptd_pt',
			'posts_per_page'	=> -1,
			'orderby' 			=> 'post_title',
			'order' 			=> 'ASC'
		));

		# update the object and return if we didn't find any post types
		if( ! $post_types ) {
			self::$no_post_types = true;
			return array();
		}

		# load the post types
		foreach($post_types as $post){
			self::$post_types[] = new CPTD_pt( $post );
		}
		return self::$post_types;
	} # end: get_post_types()

	/**
	 * Return and/or populate self::$taxonomies array
	 * @since 	2.0.0
	 * @return 	array 	May be empty.
	 */
	public static function get_taxonomies() {

		# see if the taxonomies are already loaded
		if(self::$taxonomies) return self::$taxonomies;
		elseif(self::$no_taxonomies) return array();

		# query for the cptd_tax post type
		$taxonomies = get_posts( array(
			'post_type' 		=> 'cptd_tax',
			'posts_per_page' 	=> -1,
			'orderby' 			=> 'post_title',
			'order' 			=> 'ASC'
		));

		# update the object and return if we didn't find any taxonomies
		if(!$taxonomies){
			self::$no_taxonomies = true;
			return array();
		}

		foreach($taxonomies as $tax){
			self::$taxonomies[] = new CPTD_tax( $tax );
		}
		return self::$taxonomies;
	} # end: get_taxonomies()
} # end class: CPTD