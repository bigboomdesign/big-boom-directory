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
	 * List of post IDs for CPTD post types
	 *
	 * @param 	array 	
	 * @since 	2.0.0
	 */
	public static $post_type_ids = array();

	/**
	 * List of post types created by CPTD user (stdClass objects retrieved from DB) 
	 *
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
	 * List of post IDs for CPTD taxonomies
	 *
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
	 * @since 	2.0.0
	 */
	public static $meta = array();

	/**
	 * An alphabetical list of unique field keys for all CPTD user-created posts
	 *
	 * @param 	array
	 * @since 	2.0.0
	 */
	public static $all_field_keys = null;

	/**
	 * All ACF field groups (WP_Post) objects 
	 * 
	 * @param 	array
	 * @since 	2.0.0
	 */
	public static $acf_field_groups = array();

	/**
	 * Whether we've already checked and found no ACF field groups
	 *
	 * @param 	bool
	 * @since 	2.0.0
	 */
	public static $no_acf_field_groups = false;

	/**
	 * Whether we're viewing a CPTD object on the front end
	 * 
	 * @param 	bool
	 * @since 	2.0.0
	 */
	public static $is_cptd = null;

	/**
	 * The current front end view type (null if ! self::$is_cptd )
	 *
	 * @param 	string 		(null|archive|single)
	 * @since 	2.0.0
	 */
	public static $view_type = null;

	/**
	 * The post type post ID for the current view
	 * 
	 * @param 	string
	 * @since 	2.0.0
	 */
	public static $current_post_type = '';

	/**
	 * The taxonomy post ID for the current view
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
	 * - Actions
	 * 		- wp()
	 * 		- pre_get_posts()
	 * 		- enqueue_scripts()
	 *
	 * - Filters
	 * 		- the_content()
	 */

	/**
	 * Callback for `wp` action
	 *
	 * @since 	2.0.0
	 */
	public static function wp() {

		global $cptd_view;

		self::load_view_info();

		# make sure we're viewing a CPTD object
		if( ! is_cptd_view() ) return;

		add_action( 'wp_enqueue_scripts', array( 'CPTD', 'enqueue_scripts' ) );

		$cptd_view = new CPTD_View();

		# load the post meta that we'll need for this view
		$cptd_view->load_post_meta();
		
		add_filter( 'the_content', array( 'CPTD', 'the_content' ) );
		add_filter( 'the_excerpt', array( 'CPTD', 'the_content' ) );
	
	} # end: wp()

	/**
	 * Callback for 'pre_get_posts' action
	 *
	 * Defines a custom action 'cptd_pre_get_posts' after validating the view as CPTD
	 *
	 * @param 	WP_Query 	$query 		The query object that is getting posts
	 * @since 	2.0.0
	 */
	public static function pre_get_posts( $query ) {

		# make sure we have the main query
		if( ! $query->is_main_query() ) return;

		# Use post title as the default ordering for CPTD views
		$query->query_vars['orderby'] = 'post_title';
		$query->query_vars['order'] = 'ASC';

		# action that users can hook into to edit the query further
		do_action( 'cptd_pre_get_posts', $query );

	} # end: pre_get_posts()

	/**
	 * Enqueue styles and javascripts
	 *
	 * @since 	2.0.0
	 */
	public static function enqueue_scripts() {
		wp_enqueue_style( 'cptd-css', cptd_url( '/css/cptd.css' ) );

		# font awesome
		wp_enqueue_style( 'cptd-fa', '//maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css');

		# lightbox
		wp_enqueue_script('cptd-lightbox', cptd_url('/assets/lightbox/lightbox.min.js'), array('jquery'));
		wp_enqueue_style('cptd-lightbox', cptd_url('/assets/lightbox/lightbox.css'));
	} # end: enqueue_scripts()

	/**
	 * Callback for 'the_content' and 'the_excerpt' action
	 *
	 * @param 	string 	$content 	The post content or excerpt
	 * @return 	string 	The new post content after being filtered
	 * @since 	2.0.0
	 */
	public static function the_content( $content ) {

		# do nothing if we're not viewing a CPTD object
		if( ! is_cptd_view() ) return $content;

		global $cptd_view;

		$html = '';

		# check if we have HTML to display based on ACF field data
		if( ! empty( $cptd_view->acf_fields ) ) {

			$html .= $cptd_view->get_acf_html();

		} # end if: ACF fields are set for the current view

		# prepend the CPTD HTML to the content
		$output = $html . $content;

		# apply a filter the user can hook into and return the modified content
		$output = apply_filters( 'cptd_the_content', $html . $content );

		return $output;

	} # end: the_content()


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

		# build the posts query
		$posts_query = "SELECT ID, post_title, post_type, post_status FROM " . $wpdb->posts .
			" WHERE post_type IN ( 'cptd_pt', 'cptd_tax' ) " .
			" AND post_status IN ( 'publish', 'draft' ) " .
			" ORDER BY post_title ASC";

		# execute the query
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
	 * Get all ACF field groups.  Returns and/or populates self::$acf_field_groups
	 *
	 * @since 	2.0.0
	 */
	public static function get_acf_field_groups() {

		# if we've run this function before, return the result
		if( self::$acf_field_groups || self::$no_acf_field_groups ) return self::$acf_field_groups;

		$field_groups = get_posts( array(
				'posts_per_page' => -1,
				'post_type' => array( 'acf-field-group', 'acf' ),
				'post_status' => 'publish',
		));

		if( ! $field_groups ) {
			self::$no_acf_field_groups = true;
		}
		
		CPTD::$acf_field_groups = $field_groups;
		return CPTD::$acf_field_groups;

	} # end: get_acf_field_groups()

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
		
		# change from null to false, to indicate we've loaded the view info already and found this wasn't a CPTD view
		CPTD::$is_cptd = false;

		global $wp_query;

		# reduce weight for pages, posts, post archive, categories, tags, and author archives
		if( is_page() || is_singular('post') || is_home() || is_category() || is_tag() || is_author() ) {
			return;
		}

		# if we are doing a wp search
		if( is_search() ) { 
			CPTD::$is_cptd = true;
			CPTD::$view_type = 'archive';
			return;
		}

		# make sure the CPTD post data is loaded
		if( empty( self::$post_type_ids ) || empty( self::$taxonomy_ids ) ) self::load_cptd_post_data();

		# see if there is a queried post type for this view
		$queried_post_type = ( isset( $wp_query->query_vars['post_type'] ) ? $wp_query->query_vars['post_type'] : '' );

		if( $queried_post_type ) {

			# loop through CPTD post types and check against queried post type
			foreach( CPTD::$post_type_ids as $pt) {

				$pt = new CPTD_PT( $pt );
				if( empty( $pt->handle ) ) continue;

				# if the queried post type is a CPTD post type
				if( $queried_post_type == $pt->handle ) {

					CPTD::$is_cptd = true;
					
					# set the current post type
					CPTD::$current_post_type = $pt->ID;

					if( is_singular() ) CPTD::$view_type = 'single';
					else CPTD::$view_type = 'archive';
				}

			} # end foreach: post type IDs

		} # end if: queried post type exists

		# see if there is a queried taxonomy for this view
		$tax_query = ( ! empty( $wp_query->tax_query ) ? $wp_query->tax_query : '');

		if( $tax_query ) {
		
			# loop through CPTD taxonomies and check against queried taxonomies
			foreach( CPTD::$taxonomy_ids as $tax ) {

				$tax = new CPTD_Tax( $tax );
				foreach( $tax_query->queries as $query ) {

					# if the queried taxonomy is a CPTD taxonomy
					if( $query['taxonomy'] == $tax->handle ) {

						CPTD::$is_cptd = true;
						CPTD::$current_taxonomy = $tax->ID;
						CPTD::$view_type = 'archive';
					}
				}
			} # end foreach: taxonomy IDs
		} # end if: tax query exists

	} # end: load_view_info()

} # end class: CPTD