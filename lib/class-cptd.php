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

	/**
	 * Class parameters 
	 */

	/**
	 * List of post IDs for CPT post types
	 * @param 	array 	
	 * @since 	2.0.0
	 */
	public static $post_type_ids = array();

	/**
	 * List of post types created by CPTD user (stdClass objects retrieved from DB) 
	 * @param 	array 	
	 * @since 	2.0.0
	 */
	public static $post_types = array();

	/**
	 * Whether we've tried loading post types and found none (to prevent querying again)
	 * 
	 * @param 	bool
	 * @since 	2.0.0
	 */
	public static $no_post_types = false;

	/**
	 * List of post IDs for CPT taxonomies
	 * @param 	array 	
	 * @since 	2.0.0
	 */
	public static $taxonomy_ids = array();

	/**
	 * List of taxonomies created by CPTD (stdClass objects retrieved from DB)
	 * 
	 * @param 	array
	 * @since 	2.0.0
	 */
	public static $taxonomies = array();

	/**
	 * Whether we've tried loading taxonomies and found none (to prevent querying again)
	 * 
	 * @param 	bool
	 * @since 	2.0.0
	 */
	public static $no_taxonomies = false;

	/**
	 * The meta data for all `cptd_pt` and `cptd_tax` post types, indexed by post ID
	 *
	 * This static variable holds data directly from the database and won't necessarily reflect the 
	 * state of any objects that use the data for instantiation. For example, field values that are 
	 * serialized arrays will not be unserialized here.
	 *
	 * @param 	array 	$meta {
	 *
	 *		@type 	(int) $post_id => (stdClass) $field {
	 *	
	 *			@type 	int 	$post_id
	 * 			@type 	string	$meta_key		A `_cptd_meta_` field key, with the `_cptd_meta_` part removed
	 *			@type 	string	$meta_value	
	 * 		}
	 * }
	 */
	public static $meta = array();

	/**
	 * Whether we're viewing a CPTD object on the front end
	 * 
	 * @param 	bool
	 * @since 	2.0.0
	 */
	public static $is_cptd = null;

	/**
	 * The post type for the current view
	 * 
	 * @param 	string
	 * @since 	2.0.0
	 */
	public static $current_post_type = '';

	/**
	 * The taxonomy for the current view
	 * 
	 * @param 	string
	 * @since 	2.0.0
	 */
	public static $current_taxonomy = '';


	/**
	 * Class methods
	 * 
	 * - Basic WP callbacks for actions and filters
	 * - Retrieve and store static information about post types and taxonomies
	 */

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
	 * - load_cptd_post_data()
	 * - get_post_types()
	 * - get_taxonomies()
	 * - load_view_info()
	 */

	/**
	 * Load all data necessary to bootstrap the custom post types and taxonomies
	 * 
	 * @since 	2.0.0
	 */ 
	public static function load_cptd_post_data() {

		# query the database for post type 'cptd_pt' and 'cptd_tax'
		global $wpdb;

		$posts_query = "SELECT ID, post_title, post_type, post_status FROM " . $wpdb->posts .
			" WHERE post_type IN ( 'cptd_pt', 'cptd_tax' )" . 
			" AND post_status IN ( 'publish', 'draft' )" . 
			" ORDER BY post_title ASC";

		$posts = $wpdb->get_results( $posts_query );
		
		# if we don't have any post types or taxonomies, set the object values to indicate so
		if( ! $posts ) {
			self::$no_post_types = true;
			self::$no_taxonomies = true;
			return;
		}

		# whether we have each type of post
		$has_post_type = false;
		$has_taxonomy = false;

		# loop through posts and load the IDs
		foreach( $posts as  $post ) {

			# for post types
			if( 'cptd_pt' == $post->post_type ) {
				$has_post_type = true;
				self::$post_type_ids[] = $post->ID;
				self::$post_types[ $post->ID ] = $post;
			}

			# for taxonomies
			elseif( 'cptd_tax' == $post->post_type ){
				$has_taxonomy = true;
				self::$taxonomy_ids[] = $post->ID;
				 self::$taxonomies[ $post->ID ] = $post;
			}			
		} # end foreach: $posts for post types and taxonomies

		# set the object state for post type and taxonomy existence
		if( ! $has_post_type ) self::$no_post_types = true;
		if( ! $has_taxonomy ) self::$no_taxonomies = true;

		# get the post meta that makes the post types and taxonomies work
		$post_meta_query = "SELECT post_id, meta_key, meta_value FROM " . $wpdb->postmeta . 
			" WHERE meta_key LIKE '_cptd_meta_%'";
		$post_meta = $wpdb->get_results( $post_meta_query );

		# parse the post meta data rows
		foreach( $post_meta as $field ) {

			# get the simplified key (e.g. `handle` instead of `_cptd_meta_handle`)
			$key = str_replace( '_cptd_meta_', '', $field->meta_key );

			if( ! $key  ) continue;

			# create the ($ID) => (stdClass) entry in self::$meta to hold the field keys and values if it doesn't exist
			if( ! array_key_exists( $field->post_id, self::$meta ) ) self::$meta[ $field->post_id ] = new stdClass();

			# store the field value in self::$meta
			self::$meta[ $field->post_id ]->$key = $field->meta_value;

		} # end foreach: $post_meta

	} # end: load_cptd_post_data()


	/**
	 * Return the self::$post_types array. Executes self::load_cptd_post_data if necessary
	 *
	 * @return 	array 	May be empty.
	 * @since 	2.0.0
	 */
	public static function get_post_types() {

		# see if the post types are already loaded
		if( self::$post_types ) return self::$post_types;
		elseif( self::$no_post_types ) return array();

		self::load_cptd_post_data();
		return self::$post_types;
		
	} # end: get_post_types()

	/**
	 * Return and/or populate self::$taxonomies array
	 *
	 * @return 	array 	May be empty.
	 * @since 	2.0.0
	 */
	public static function get_taxonomies() {

		# see if the taxonomies are already loaded
		if( self::$taxonomies ) return self::$taxonomies;
		elseif( self::$no_taxonomies ) return array();

		self::load_cptd_post_data();
		return self::$taxonomies;

	} # end: get_taxonomies()


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

		global $wp_query;
		if( empty( $wp_query ) ) return;

		# reduce weight for pages, post archives, categories, tags, and author archives
		if( is_page() || is_home() || is_category() || is_tag() || is_author() ) {
			CPTD::$is_cptd = false;
			return;
		}

		# make sure the CPTD post data is loaded
		if( empty( self::$post_type_ids ) || empty( self::$taxonomy_ids ) ) self::load_cptd_post_data();

		# see if there is a queried post type for this view
		$queried_post_type = ( isset( $wp_query->query_vars['post_type'] ) ? $wp_query->query_vars['post_type'] : '' );

		if( $queried_post_type ) {

			# loop through CPTD post types and check against queried post type
			foreach( CPTD::$post_type_ids as $pt) {

				$pt = new CPTD_pt( $pt );
				if( empty( $pt->handle ) ) continue;

				if( $queried_post_type == $pt->handle ) {

					CPTD::$is_cptd = true;
					
					# set the current post type
					CPTD::$current_post_type = $pt->handle;
				}

			} # end foreach: post type IDs
		}

		# see if there is a queried taxonomy for this view
		$tax_query = ( ! empty( $wp_query->tax_query ) ? $wp_query->tax_query : '');

		if( $tax_query ) {
		
			# loop through CPTD taxonomies and check against queried taxonomies
			foreach( CPTD::$taxonomy_ids as $tax ) {

				$tax = new CPTD_tax( $tax );
				foreach( $tax_query->queries as $query ) {
					if( $query['taxonomy'] == $tax->handle ) {
						CPTD::$is_cptd = true;
						CPTD::$current_taxonomy = $tax->handle;
					}
				}
			} # end foreach: taxonomy IDs
		}

	} # end: load_view_info()
} # end class: CPTD