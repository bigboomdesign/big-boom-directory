<?php
/**
 * Handles callbacks for front end WP actions and filters
 * Handles callbacks for shortcodes
 * Handles callbacks for custom front end actions and filters
 * Stores and retrieves static information about post types and taxonomies created by the plugin
 *
 * @since 2.0.0
 */
class CPTD {

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
	 * A list of post ID's belonging to user-created post types
	 *
	 * @param 	array
	 * @since 	2.0.0
	 */
	public static $all_post_ids = null;

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
	 * - Callbacks for shortcodes
	 * - Store and retrieve and store static information about post types and taxonomies
	 */

	/**
	 * Basic WP callbacks for actions and filters
	 *
	 * - Actions
	 * 		- init()
	 * 		- widgets_init()
	 * 		- pre_get_posts()
	 * 		- wp()
	 * 		- enqueue_scripts()
	 * 		- loop_start()
	 *
	 * - Filters
	 * 		- the_content()
	 */

	/**
	 * Callback for 'init' action
	 *
	 * @since 	2.0.0
	 */
	public static function init() {

		# shortcodes
		add_shortcode( 'cptd-a-z-listing', array('CPTD', 'a_to_z_html') );
		add_shortcode( 'cptd-terms', array('CPTD', 'terms_html') );

	} # end: init()

	/** 
	 * Register the widgets for the plugin
	 */
	public static function widgets_init() {
		register_widget("CPTD_Search_Widget");
		register_widget("CPTD_Random_Posts_Widget");
	} # end: widgets_init()

	/**
	 * Callback for 'pre_get_posts' action
	 *
	 * Executes a custom action 'cptd_pre_get_posts' after validating the view as CPTD
	 *
	 * Initializes the following static variables
	 *
	 * - CPTD::$is_cptd
	 * - CPTD::$current_post_type
	 * - CPTD::$current_taxonomy
	 *
	 * @param 	WP_Query 	$query 		The query object that is getting posts
	 * @since 	2.0.0
	 */
	public static function pre_get_posts( $query ) {

		# make sure we have the main query
		if( ! $query->is_main_query() ) return;

		# the value of CPTD::$is_cptd hasn't been set when this hook fires
		self::$is_cptd = false;

		# The CPTD_PT object for the current view
		$current_post_type = '';

		# The CPTD_Tax object for the current view
		$current_taxonomy = '';

		$post_order = '';

		# see if the query has a post type set
		if( isset( $query->query['post_type'] ) ) {

			# get the post type name for the query
			$queried_post_type = $query->query['post_type'];

			# loop through CPTD post types and see if any of them match the current query
			foreach( CPTD::$post_type_ids  as $post_id ) {

				$pt = new CPTD_PT( $post_id );

				if( $queried_post_type == $pt->handle ) {
					self::$is_cptd = true;
					$current_post_type = $pt;
					self::$current_post_type = $pt->ID;
				}
			}
		} # end if: query has a post type set

		# see if the query has a taxonomy set
		if( ! empty( $query->tax_query->queries ) ) {

			$tax_queries = $query->tax_query->queries;

			# make sure we have exactly one taxonomy set, otherwise we won't consider this a CPTD view
			if( 1 != count( $tax_queries ) ) return;
			
			# get the taxonomy name for the query
			$queried_taxonomy = $tax_queries[0]['taxonomy'];

			foreach( CPTD::$taxonomy_ids as $tax_id ) {

				$tax = new CPTD_Tax( $tax_id );
				if( $tax->handle == $queried_taxonomy ) {

					self::$is_cptd = true;
					$current_taxonomy = $tax;
					self::$current_taxonomy = $tax->ID;

					# if the current post type isn't set, use the first post type tied to the current taxonomy
					if( ! $current_post_type ) {

						$current_post_type = new CPTD_PT( $current_taxonomy->post_types[0] );
						self::$current_post_type = $current_post_type->ID;
					}
				}
			}
		} # end if: query has a taxonomy set

		if( ! CPTD::$is_cptd ) return;
		if( empty( $current_post_type ) ) return;

		# get the post orderby parameter
		$orderby = $current_post_type->post_orderby;
		if( ! $orderby ) $orderby = 'title';

		# the order parameter
		$order = $current_post_type->post_order;

		# the meta key for ordering
		$meta_key = $current_post_type->meta_key_orderby;

		$query->query_vars['orderby'] = $orderby;
		$query->query_vars['order'] = $order;

		# when ordering by meta value
		if( $meta_key && ( 'meta_value' == $orderby || 'meta_value_num' == $orderby ) ) {

			# set the meta key argument
			$query->query_vars['meta_key'] = $meta_key;

			# make sure that we filter out posts with the meta value saved as an empty string
			# these posts appear at the top otherwise
			$query->query_vars['meta_query'][] = array(
				'key' => $meta_key,
				'value' => '',
				'compare' => '!=',
			);

		} # end if: ordering by custom field

		# action that users can hook into to edit the query further
		do_action( 'cptd_pre_get_posts', $query );

	} # end: pre_get_posts()

	/**
	 * Callback for `wp` action
	 *
	 * @since 	2.0.0
	 */
	public static function wp() {

		global $cptd_view;

		self::load_view_info();

		# if we're not viewing a CPTD object
		if( ! is_cptd_view() ) return;

		add_action( 'wp_enqueue_scripts', array( 'CPTD', 'enqueue_scripts' ) );

		$cptd_view = new CPTD_View();

		# load the post meta that we'll need for this view
		$cptd_view->load_post_meta();

		add_filter( 'loop_start', array( 'CPTD', 'loop_start' ) );
		
		add_filter( 'the_content', array( 'CPTD', 'the_content' ) );
		add_filter( 'the_excerpt', array( 'CPTD', 'the_content' ) );

		do_action( 'cptd_wp' );
	
	} # end: wp()

	/**
	 * Enqueue styles and javascripts
	 *
	 * @since 	2.0.0
	 */
	public static function enqueue_scripts() {

		wp_enqueue_style( 'cptd', cptd_url( '/css/cptd.css' ) );

		# font awesome
		wp_enqueue_style( 'cptd-fa', '//maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css');

		# lightbox
		wp_enqueue_script('cptd-lightbox', cptd_url('/assets/lightbox/lightbox.min.js'), array('jquery'));
		wp_enqueue_style('cptd-lightbox', cptd_url('/assets/lightbox/lightbox.css'));

		do_action( 'cptd_enqueue_scripts' );

	} # end: enqueue_scripts()

	/**
	 * Callback for WP loop_start hook. Inserts post type description before post type archive
	 *
	 * @param 	WP_Query 	$query 		The query whose loop is starting
	 * @since 	2.0.0
	 */
	public static function loop_start( $query ) {
		
		# make sure we have the main query
		if( ! $query->is_main_query() ) return;

		# we're only wanting to hook on post type archive pages
		if( ! is_post_type_archive() || empty( CPTD::$current_post_type ) ) return;

		do_action( 'cptd_before_pt_description' );

		# get the current post type object
		$pt = new CPTD_PT( CPTD::$current_post_type );

		# get the post content for the current post type
		$post_type_description = get_post_field( 'post_content', $pt->ID );

		# make sure we have content to display
		if( empty( $post_type_description ) ) return;

		# the wrapper for the post type description 
		$wrap = array(
			'before_tag' 	=> 'div',
			'after_tag' 	=> 'div',
			'classes'		=> array('cptd-post-type-description'),
			'id'			=> '',
		);
		# apply a hookable filter for the wrapper
		$wrap = apply_filters( 'cptd_pt_description_wrap', $wrap );

		# show the post type description
		if( ! empty( $wrap['before_tag'] ) ) {
		?>
			<<?php 
				echo $wrap['before_tag'] . ' ';
				if( ! empty( $wrap['classes'] ) ) echo 'class="' . implode( ' ', $wrap['classes'] ) . '" ';
				if( ! empty( $wrap['id'] ) ) echo 'id="' . $wrap['id'] . '"';
				
			?>>
		<?php
		} # end if: wrap has an opening tag
			echo apply_filters( 'the_content', $post_type_description );

		if( ! empty( $wrap['after_tag'] ) ) {
		?>
			</<?php echo $wrap['after_tag']; ?>>
		<?php
		}

		do_action( 'cptd_after_pt_description' );

	} # end: loop_start()

	/**
	 * Callback for 'the_content' and 'the_excerpt' action
	 *
	 * @param 	string 	$content 	The post content or excerpt
	 * @return 	string 	The new post content after being filtered
	 * @since 	2.0.0
	 */
	public static function the_content( $content ) {

		# if we're doing the loop_start action, we don't want to append fields
		if( doing_action('loop_start') ) return $content;

		global $cptd_view;

		$html = '';

		# check if we have HTML to display based on ACF field data
		if( ! empty( $cptd_view->acf_fields ) ) {

			$html .= $cptd_view->get_acf_html();

		} # end if: ACF fields are set for the current view

		# prepend the CPTD HTML to the content
		$output = $html . $content;

		# apply a filter the user can hook into and return the modified content
		$output = apply_filters( 'cptd_the_content', $output );

		return $output;

	} # end: the_content()

	/**
	 * Callbacks for shortcodes
	 * 
	 * - a_to_z_html()
	 * - terms_html()
	 */

	/**
	 * Generate HTML for the `cptd-a-z-listing` shortcode
	 *
	 * @return 	string
	 * @since 	2.0.0
	 */
	public static function a_to_z_html( $atts ) {

		# get the attributes with defaults
		$atts = shortcode_atts( array(
			'post_types' => '',
			'list_style' => '',
		), $atts, 'cptd-a-z-listing');

		# validate the list style
		$list_style = $atts['list_style'];
		if( ! in_array( $list_style, array( 'none', 'inherit', 'disc', 'circle', 'square' ) ) )
			$list_style = '';

		# get the post types
		$post_types = $atts['post_types'];
		
		# turn the string into an array if post types are set
		if( ! empty( $post_types ) ) {

			$post_types =  array_map( 'trim' , explode(  ',', $post_types ) );
		}

		# if no post types are given, use all CPTD post types
		if( empty( $post_types ) ) {
			$post_types = self::get_post_type_names();
		}
		
		if( empty( $post_types ) ) return '';

		# get the posts for the A-Z listing using the given post types
		$posts = get_posts(array(
			'posts_per_page' => -1,
			'orderby' => 'post_title',
			'order' => 'ASC',
			'post_type' => $post_types,
		));

		if( empty( $posts ) ) return '';

		# if we have posts, enqueue the CPTD stylesheet in the footer
		wp_enqueue_style( 'cptd', cptd_url( '/css/cptd.css' ), true );

		# generate the HTML
		ob_start();
	?>
		<div id='cptd-a-z-listing'>
			<ul>
				<?php foreach( $posts as $post ) { ?>
					<li
						<?php 
							if( ! empty( $list_style ) ) echo 'style="list-style: ' . $list_style .'"'; 
						?>
					><a href="<?php echo get_permalink( $post->ID ); ?>"><?php echo $post->post_title; ?></a></li>
				<?php } ?>
			</ul>
		</div>
	<?php
		$html = ob_get_contents();
		ob_end_clean();

		return $html;

	} # end: a_to_z_html()

	/**
	 * Generate HTML for `cptd-terms` shortcode
	 *
	 * @return 	string
	 * @since 	2.0.0
	 */
	public static function terms_html( $atts = array() ) {

		# get the attributes with defaults
		$atts = shortcode_atts( array(
			'taxonomies' => '',
			'list_style' => '',
		), $atts, 'cptd-terms');

		# validate the list style
		$list_style = $atts['list_style'];
		if( ! in_array( $list_style, array( 'none', 'inherit', 'disc', 'circle', 'square' ) ) )
			$list_style = '';

		# get the taxonomy names
		# if none are given, we'll use all CPTD taxonomies
		$taxonomy_names = array();

		# if we are passed taxonomies
		if( ! empty( $atts['taxonomies'] ) ) {

			$taxonomy_names = array_map( 'trim', explode( ',', $atts['taxonomies'] ) );

		} # end if: taxonomies were given

		# if no taxonomies were given or found, try to use all CPTD taxonomies
		if( empty( $taxonomy_names ) ) {
			
			$taxonomy_names = CPTD::get_taxonomy_names();

			# do nothing if no taxonomies are registered and none are given to us
			if( empty( $taxonomy_names ) ) return '';

		}

		# get the terms for the taxonomies
		$terms = get_terms( $taxonomy_names );

		# make sure we have terms
		if( empty( $terms ) || is_wp_error( $terms ) ) return '';

		# generate HTML for list
		ob_start();
		?>
		<div id="cptd-terms">
			<ul>
				<?php
				foreach($terms as $term){
				?>
					<li <?php if( $list_style ) echo 'style="list-style: ' . $list_style . '"'; ?>>
						<a href="<?php echo get_term_link( $term ); ?>" >
							<?php echo $term->name; ?>
						</a>
					</li>
				<?php
				}
				?>
			</ul>
		</div>
		<?php

		# enqueue the CPTD stylesheet in the footer
		wp_enqueue_style( 'cptd', cptd_url('/css/cptd.css'), true );

		$html = ob_get_contents();
		ob_end_clean();
		return $html;

	} # end: terms_html()


	/**
	 * Store and retrieve and store static information about post types and taxonomies
	 *
	 * - load_cptd_post_data()
	 * - get_post_types()
	 * - get_taxonomies()
	 * - get_post_type_objects()
	 * - get_post_type_names()
	 * - get_taxonomy_objects()
	 * - get_taxonomy_names()
	 * - get_acf_field_groups()
	 * - load_view_info()
	 */

	/**
	 * Load all data necessary to bootstrap the custom post types and taxonomies
	 *
	 * @since 	2.0.0
	 */ 
	public static function load_cptd_post_data() {

		# make sure we only call the function one time
		if( self::$no_post_types || ! empty( self::$post_type_ids ) ) return;

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
	 * Return and/or populate self::$post_types array. Executes self::load_cptd_post_data if necessary
	 *
	 * @return 	array 	May be empty.
	 * @since 	2.0.0
	 */
	public static function get_post_types() {

		# see if the post types are already loaded
		if( self::$post_types ) return self::$post_types;

		# if we have already loaded post types and none were found
		elseif( self::$no_post_types ) return array();

		# load the post data and return
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
	 * Return an array of CPTD_PT objects for the registered post types
	 *
	 * @return 	array 	May be empty.
	 * @since 	2.0.0
	 */
	public static function get_post_type_objects() {

		$post_type_objects = array();

		# get the active post types
		$post_types = self::get_post_types();
		foreach( $post_types as $post_type ) {
			
			$pt = new CPTD_PT( $post_type->ID );
			$post_type_objects[] = $pt;
		}

		return $post_type_objects;

	} # end: get_post_type_objects()

	/**
	 * Return a list of CPTD post type names
	 *
	 * @return 	array 	May be empty.
	 * @since 	2.0.0
	 */
	public static function get_post_type_names() {

		$post_type_names = array();

		$post_type_objects = self::get_post_type_objects();
		foreach( $post_type_objects as $pt ) {
			$post_type_names[] = $pt->handle;
		}

		return $post_type_names;

	} # end: get_post_type_names()

	/**
	 * Return an array of CPTD_Tax objects for CPTD taxonomies
	 *
	 * @return 	array 	May be empty.
	 * @since 	2.0.0
	 */
	public static function get_taxonomy_objects() {

		$taxonomy_objects = array();

		# get the active post types
		$taxonomies = self::get_taxonomies();
		foreach( $taxonomies as $taxonomy ) {
			
			$tax = new CPTD_Tax( $taxonomy->ID );
			$taxonomy_objects[] = $tax;
		}

		return $taxonomy_objects;

	} # end: get_taxonomy_objects

	/**
	 * Return an array of CPTD taxonomy names
	 *
	 * @param	array 	May be empty.
	 * @since 	2.0.0
	 */
	public static function get_taxonomy_names() {

		$taxonomy_names = array();

		$taxonomy_objects = self::get_taxonomy_objects();
		foreach( $taxonomy_objects as $tax ) {
			$taxonomy_names[] = $tax->handle;
		}

		return $taxonomy_names;

	} # end: get_taxonomy_names()

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
	 * - CPTD::$view_type
	 * - CPTD::$is_cptd (if is_search() is true)
	 * 
	 * @since 	2.0.0
	 */
	public static function load_view_info() {

		# reduce weight for non-plugin views
		if( ! is_search() && ! is_cptd_view() ) return;

		# if we are doing a wp search
		if( is_search() ) { 
			self::$is_cptd = true;
			self::$view_type = 'archive';
			return;
		}

		# make sure the CPTD post data is loaded
		if( empty( self::$post_type_ids ) || empty( self::$taxonomy_ids ) ) self::load_cptd_post_data();

		# see if there is a queried post type for this view
		if( self::$current_post_type ) {
			if( is_singular() ) self::$view_type = 'single';
			else self::$view_type = 'archive';
		}

	} # end: load_view_info()

} # end class: CPTD