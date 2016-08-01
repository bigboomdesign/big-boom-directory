<?php
/**
 * Handles callbacks for front end WP actions and filters
 * Handles callbacks for shortcodes
 * Handles callbacks for custom front end actions and filters
 * Stores and retrieves static information about post types and taxonomies created by the plugin
 *
 * @since 2.0.0
 */
class BBD {

	/**
	 * Class parameters 
	 */

	/**
	 * List of post IDs for BBD post types
	 *
	 * @param 	array 	
	 * @since 	2.0.0
	 */
	public static $post_type_ids = array();

	/**
	 * List of post types created by BBD user (stdClass objects retrieved from DB) 
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
	 * List of post IDs for BBD taxonomies
	 *
	 * @param 	array 	
	 * @since 	2.0.0
	 */
	public static $taxonomy_ids = array();

	/**
	 * List of taxonomies created by BBD (stdClass objects retrieved from DB)
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
	 * The meta data for all `bbd_pt` and `bbd_tax` post types, indexed by post ID
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
	 * 			@type 	string	$meta_key		A `_bbd_meta_` field key, with the `_bbd_meta_` part removed
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
	 * An alphabetical list of unique field keys for all BBD user-created posts
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
	 * Whether we're viewing a BBD object on the front end
	 * 
	 * @param 	bool
	 * @since 	2.0.0
	 */
	public static $is_bbd = null;

	/**
	 * The current front end view type (null if ! self::$is_bbd )
	 *
	 * @param 	string 		(null|archive|single|bbd-search-results)
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
	 * - Helper Functions
	 */

	/**
	 * Basic WP callbacks for actions and filters
	 *
	 * - Actions
	 * 		- init()
	 * 		- widgets_init()
	 * 		- pre_get_posts()
	 * 		- wp()
	 * 		- add_edit_post_type_to_admin_bar()
	 * 		- enqueue_scripts()
	 * 		- loop_start()
	 * 		- updated_postmeta()
	 *
	 * - Filters
	 * 		- the_content()
	 *		- the_excerpt()
	 */

	/**
	 * Callback for 'init' action
	 *
	 * @since 	2.0.0
	 */
	public static function init() {
		
		# load the data necessary to register post types and taxonomies
		BBD::load_bbd_post_data();

		# register post types and taxonomies
		BBD_Helper::register();

		# shortcodes
		add_shortcode( 'bbd-a-z-listing', array('BBD', 'a_to_z_html') );
		add_shortcode( 'bbd-terms', array('BBD', 'terms_html') );

	} # end: init()

	/** 
	 * Register the widgets for the plugin
	 *
	 * @since 	2.0.0
	 */
	public static function widgets_init() {
		register_widget("BBD_Search_Widget");
	} # end: widgets_init()

	/**
	 * Callback for 'pre_get_posts' action
	 *
	 * Executes a custom action 'bbd_pre_get_posts' after validating the view as BBD
	 *
	 * Initializes the following static variables
	 *
	 * - BBD::$is_bbd
	 * - BBD::$current_post_type
	 * - BBD::$current_taxonomy
	 *
	 * @param 	WP_Query 	$query 		The query object that is getting posts
	 * @since 	2.0.0
	 */
	public static function pre_get_posts( $query ) {

		# make sure we have the main query
		if( ! $query->is_main_query() ) return;

		# the value of BBD::$is_bbd hasn't been set when this hook fires
		self::$is_bbd = false;

		# The BBD_PT object for the current view
		$current_post_type = '';

		# The BBD_Tax object for the current view
		$current_taxonomy = '';

		$post_order = '';

		# see if the query has a post type set
		if( isset( $query->query['post_type'] ) ) {

			# get the post type name for the query
			$queried_post_type = $query->query['post_type'];

			# loop through BBD post types and see if any of them match the current query
			foreach( BBD::$post_type_ids  as $post_id ) {

				$pt = new BBD_PT( $post_id );

				if( $queried_post_type == $pt->handle ) {
					self::$is_bbd = true;
					$current_post_type = $pt;
					self::$current_post_type = $pt->ID;
				}
			}
		} # end if: query has a post type set

		# see if the query has a taxonomy set
		if( ! empty( $query->tax_query->queries ) ) {

			$tax_queries = $query->tax_query->queries;

			# make sure we have exactly one taxonomy set, otherwise we won't consider this a BBD view
			if( 1 != count( $tax_queries ) ) return;
			
			# get the taxonomy name for the query
			$queried_taxonomy = $tax_queries[0]['taxonomy'];

			foreach( BBD::$taxonomy_ids as $tax_id ) {

				$tax = new BBD_Tax( $tax_id );
				if( $tax->handle == $queried_taxonomy ) {

					self::$is_bbd = true;
					$current_taxonomy = $tax;
					self::$current_taxonomy = $tax->ID;

					# if the current post type isn't set, use the first post type tied to the current taxonomy
					if( ! $current_post_type ) {

						$current_post_type = new BBD_PT( $current_taxonomy->post_types[0] );
						self::$current_post_type = $current_post_type->ID;
					}
				}
			}
		} # end if: query has a taxonomy set

		# if we are doing search widget results
		if( ! empty( $_POST['bbd_search'] ) ) {
			self::$is_bbd = true;
		}

		if( ! BBD::$is_bbd ) return;
		if( empty( $current_post_type ) ) {

			# action that users can hook into to edit the query further
			do_action( 'bbd_pre_get_posts', $query );
			return;
		}

		/**
		 * Ordering parameters
		 *
		 * Note that forcing an order in single views can result in 404's for single listings,
		 * in the event that a post is missing a value for the orderby key
		 */
		if( ! is_singular() ) {
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
		}

		# action that users can hook into to edit the query further
		do_action( 'bbd_pre_get_posts', $query );

	} # end: pre_get_posts()

	/**
	 * Callback for `wp` action
	 *
	 * @since 	2.0.0
	 */
	public static function wp() {

		global $bbd_view;

		self::load_view_info();

		# if we're not viewing a BBD object
		if( ! is_bbd_view() ) return;

		add_action( 'wp_enqueue_scripts', array( 'BBD', 'enqueue_scripts' ) );

		$bbd_view = new BBD_View();

		# load the post meta that we'll need for this view
		$bbd_view->load_post_meta();

		# add the post type description (i.e. post content) above post archive loop
		add_filter( 'loop_start', array( 'BBD', 'loop_start' ) );

		// add link to the admin bar if we're viewing a post type archive
		if( 'archive' == $bbd_view->view_type ) {
			add_action( 'wp_before_admin_bar_render', array( 'BBD', 'add_edit_post_type_to_admin_bar' ) );
		}

		/**
		 * Add post fields the the post content or excerpt
		 *
		 * Note the callbacks are same.  We have some logic in the callback to try and ensure we're 
		 * placing the fields only once and in the correct spot for whatever theme may be in use
		 */
		add_filter( 'the_content', array( 'BBD', 'the_content' ) );
		add_filter( 'the_excerpt', array( 'BBD', 'the_content' ) );

		add_action( 'the_post', array( $bbd_view, 'reset_did_post_fields' ) );

		do_action( 'bbd_wp' );
	
	} # end: wp()

	/**
	 * Add the 'Edit Post Type' link in the WP Admin Bar when viewing a front end post type archive
	 *
	 * @since 	2.0.0
	 */
	public static function add_edit_post_type_to_admin_bar() {

		# make sure we have a post type Post ID
		if( empty( BBD::$current_post_type ) || ! intval( BBD::$current_post_type ) ) return;

		$post_type_id = intval( BBD::$current_post_type );

		global $wp_admin_bar;
		$wp_admin_bar->add_menu( array(
			'parent' => false,
			'id' => 'edit',
			'title' => __('Edit Post Type'),
			'href' => admin_url( 'post.php?post=' . $post_type_id . '&action=edit' ),
		));
	
	} # end: add_edit_post_type_to_admin_bar()

	/**
	 * Enqueue styles and javascripts
	 *
	 * @since 	2.0.0
	 */
	public static function enqueue_scripts() {

		wp_enqueue_style( 'bbd', bbd_url( '/css/bbd.css' ) );

		# font awesome
		wp_enqueue_style( 'bbd-fa', '//maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css');

		# lightbox
		wp_enqueue_script('bbd-lightbox', bbd_url('/assets/lightbox/lightbox.min.js'), array('jquery'));
		wp_enqueue_style('bbd-lightbox', bbd_url('/assets/lightbox/lightbox.css'));

		do_action( 'bbd_enqueue_scripts' );

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

		# make sure we don't execute this callback twice for themes that use loop_start more than once (e.g. twentyten)
		if( did_action( 'loop_start' ) > 1 ) return;

		# we're only wanting to hook on post type archive pages
		if( ! is_post_type_archive() || empty( BBD::$current_post_type ) ) return;

		do_action( 'bbd_before_pt_description' );

		# get the current post type object
		$pt = new BBD_PT( BBD::$current_post_type );

		# get the post content for the current post type
		$post_type_description = get_post_field( 'post_content', $pt->ID );

		# make sure we have content to display
		if( empty( $post_type_description ) ) return;

		# the wrapper for the post type description 
		$wrap = array(
			'before_tag' 	=> 'div',
			'after_tag' 	=> 'div',
			'classes'		=> array('bbd-post-type-description'),
			'id'			=> '',
		);
		# apply a hookable filter for the wrapper
		$wrap = apply_filters( 'bbd_pt_description_wrap', $wrap );

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

		do_action( 'bbd_after_pt_description' );

	} # end: loop_start()

	/**
	 * Flush the rewrite rules whenever we update the slug field for post types or taxonomies
	 *
	 * @link 	https://codex.wordpress.org/Function_Reference/flush_rewrite_rules
	 *
	 * @param 	int		$meta_id		The ID of the meta entry
	 * @param 	int		$object_id		The post whose postmeta is being updated
	 * @param 	string	$meta_key		The meta key whose value is being updated
	 * @param 	mixed	$meta_value		The new meta value after the update
	 *
	 * @since 	2.1.0
	 */
	public static function updated_postmeta( $meta_id, $object_id, $meta_key, $meta_value ) {

		# if a post type or taxonomy slug is being updated
		if( '_bbd_meta_slug' == $meta_key ) {

			set_transient( '_bbd_flush_rewrite_rules', 'true', 12 * HOUR_IN_SECONDS );
		}

		# delete the post type/taxonomy post meta from the object cache
		wp_cache_delete( 'bbd_post_types_meta' );
	
	} # end: updated_postmeta()

	/**
	 * Delete the post type data from object cache when saving a post
	 * Potentially flush the rewrite rules when saving a post
	 *
	 * Flushing the rewrite rules is done here as well as on `updated_postmeta` because 
	 * `updated_postmeta` doesn't fire when deleting or adding post meta
	 *
	 * @param 	int			$post_id	The post ID being saved
	 * @param 	WP_Post		$post		The post being saved
	 * @param 	bool		$update		Whether this is a post update (as opposed to a new post creation)
	 *
	 * @since 	2.1.0
	 */
	public static function save_post( $post_id, $post, $update ) {

		# make sure we're not doing an autosave or post revision
		if( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) return;

		# do nothing if we're not saving a post type or taxonomy
		if( 'bbd_pt' != $post->post_type && 'bbd_tax' != $post->post_type ) return;

		# delete the post types/taxonomies and related post meta from the WP Object cache
		wp_cache_delete( 'bbd_post_types' );
		wp_cache_delete( 'bbd_post_types_meta' );

		/**
		 * Potentially flush the rewrite rules
		 */

		# this covers the case where we are emptying the slug and we need to re-form the slug from the title
		if( empty( $_POST['_bbd_meta_slug'] ) ) {
			
			set_transient( '_bbd_flush_rewrite_rules', 'true', 12 * HOUR_IN_SECONDS );
			return;
		}

		# this covers the case where we previously had no slug (and were thus relying on the title) and we are
		# now adding the post meta for the slug
		if( in_array( $post_id, array_merge( BBD::$post_type_ids, BBD::$taxonomy_ids ) ) ) {
			
			# we don't need to differentiate between post types and taxonomies here
			$pt = new BBD_PT( $post_id );

			# the $_POST value will be sanitized for URL, so we need to check against this value
			if( $pt->slug != BBD_Helper::clean_str_for_url( $_POST['_bbd_meta_slug'] ) ) {

				set_transient( '_bbd_flush_rewrite_rules', 'true', 12 * HOUR_IN_SECONDS );
				return;
			}
		}
	
	} # end: save_post()

	/**
	 * Delete the post type object cache whenever a post type or taxonomy is deleted
	 * via the wp_delete_post function
	 *
	 * Note that this really only applies to instances where a post is for some reason deleted manually
	 * using the wp_delete_post function.  Otherwise, when the post is trashed via the WP backend, the cache
	 * items are already deleted because the `save_post` hook is triggered at that point.
	 *
	 * @param	int		$post_id		The post ID being deleted
	 * @since 	2.2.0
	 */
	public static function delete_post( $post_id ) {

		# do nothing if we don't have a WP_Post with a post type of bbd_pt or bbd_tax
		global $post;

		if( ! is_a( $post, 'WP_Post') ) {
			return;
		}

		if( 'bbd_pt' != $post->post_type && 'bbd_tax' != $post->post_type ) return;

		wp_cache_delete( 'bbd_post_types' );
		wp_cache_delete( 'bbd_post_types_meta' );
	}

	/**
	 * Callback for 'the_content' and 'the_excerpt' action
	 *
	 * @param 	string 	$content 	The post content or excerpt
	 * @return 	string 	The new post content after being filtered
	 *
	 * @since 	2.0.0
	 */
	public static function the_content( $content ) {

		# make sure we haven't done fields for this post yet
		global $bbd_view;
		if( $bbd_view->did_post_fields ) return $content;

		# if we're doing the loop_start action, we don't want to append fields
		if( doing_action('loop_start') ) return $content;

		/**
		 * If we're doing the_excerpt on a single post, do nothing. Lots of themes (like 2016) are placing
		 * the excerpt at the top of single posts as a preview/callout section
		 */
		if( doing_action( 'the_excerpt' ) && is_singular() ) return $content;
		
		/**
		 * If we're doing get_the_excerpt and the post has no excerpt, we shouldn't do anything, since WP will
		 * strip out the tags and leave us with unformatted fields.
		 *
		 * Note we are not checking for the_excerpt here, since this returns false during the instance we care
		 * about, which is when the_excerpt() calls get_the_excerpt() and the post content is potentially truncated
		 * and stripped of HTML tags if no excerpt exists.
		 */
		global $post;
		if( doing_action( 'get_the_excerpt' ) && empty( $post->post_excerpt ) ) return $content;

		/**
		 * If the content contains the string 'bbd-field', we'll treat this as a quasi-catch-all bail out,
		 * since this should never happen in any case
		 */
		if( false !== strpos( $content, 'bbd-field' ) ) {
			return $content;
		}

		$html = '';

		# check if we have HTML to display based on ACF field data
		if( ! empty( $bbd_view->acf_fields ) ) {

			$html .= $bbd_view->get_acf_html();

		} # end if: ACF fields are set for the current view

		# prepend the BBD HTML to the content
		$output = $html . $content;

		/**
		 * Apply a filter the user can hook into and return the modified content
		 */

		# for single view
		if( 'single' == $bbd_view->view_type ) {
			$output = apply_filters( 'bbd_the_content', $output );
		}

		# for archive view
		elseif( 'archive' == $bbd_view->view_type ) {
			$output = apply_filters( 'bbd_the_excerpt', $output );
		}

		return $output;

	} # end: the_content()

	/**
	 * Callbacks for shortcodes
	 * 
	 * - a_to_z_html()
	 * - terms_html()
	 */

	/**
	 * Generate HTML for the `bbd-a-z-listing` shortcode
	 *
	 * @return 	string
	 * @since 	2.0.0
	 */
	public static function a_to_z_html( $atts ) {

		# get the attributes with defaults
		$atts = shortcode_atts( array(
			'post_types' => '',
			'list_style' => '',
		), $atts, 'bbd-a-z-listing');

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

		# if no post types are given, use all BBD post types
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

		# if we have posts, enqueue the BBD stylesheet in the footer
		wp_enqueue_style( 'bbd', bbd_url( '/css/bbd.css' ), true );

		# generate the HTML
		ob_start();
		?>
		<div id='bbd-a-z-listing'>
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
	 * Generate HTML for `bbd-terms` shortcode
	 *
	 * @return 	string
	 * @since 	2.0.0
	 */
	public static function terms_html( $atts = array() ) {

		# get the attributes with defaults
		$atts = shortcode_atts( array(
			'taxonomies' => '',
			'list_style' => '',
		), $atts, 'bbd-terms');

		# validate the list style
		$list_style = $atts['list_style'];
		if( ! in_array( $list_style, array( 'none', 'inherit', 'disc', 'circle', 'square' ) ) )
			$list_style = '';

		# get the taxonomy names
		# if none are given, we'll use all BBD taxonomies
		$taxonomy_names = array();

		# if we are passed taxonomies
		if( ! empty( $atts['taxonomies'] ) ) {

			$taxonomy_names = array_map( 'trim', explode( ',', $atts['taxonomies'] ) );

		} # end if: taxonomies were given

		# if no taxonomies were given or found, try to use all BBD taxonomies
		if( empty( $taxonomy_names ) ) {
			
			$taxonomy_names = BBD::get_taxonomy_names();

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
		<div id="bbd-terms">
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

		# enqueue the BBD stylesheet in the footer
		wp_enqueue_style( 'bbd', bbd_url('/css/bbd.css'), true );

		$html = ob_get_contents();
		ob_end_clean();
		return $html;

	} # end: terms_html()


	/**
	 * Store and retrieve and store static information about post types and taxonomies
	 *
	 * - load_bbd_post_data()
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
	 * Gets data from the WP Object cache, or from the database if caching is not enabled or
	 * the cache items are not present
	 *
	 * @since 	2.0.0
	 */ 
	public static function load_bbd_post_data() {

		# make sure we only call the function one time
		if( self::$no_post_types || ! empty( self::$post_type_ids ) ) return;

		global $wpdb;

		# try to get post type objects from the object cache
		$post_type_objects = wp_cache_get( 'bbd_post_types' );

		if( false === $post_type_objects ) {

			# build the posts query
			$posts_query = "SELECT ID, post_title, post_type, post_status FROM " . $wpdb->posts .
				" WHERE post_type IN ( 'bbd_pt', 'bbd_tax' ) " .
				" AND post_status IN ( 'publish', 'draft' ) " .
				" ORDER BY post_title ASC";
			$post_type_objects = $wpdb->get_results( $posts_query );

			if( empty( BBD_Options::$options['disable_cache'] ) ) {
				wp_cache_set( 'bbd_post_types', $post_type_objects );
			}

		} # end if: post type objects not found in cache

		# if we don't have any post types or taxonomies, set the object values to indicate so
		if( ! $post_type_objects ) {
			self::$no_post_types = true;
			self::$no_taxonomies = true;
		}

		# whether we have each type of post
		$has_post_type = false;
		$has_taxonomy = false;

		# loop through posts and load the IDs
		foreach( $post_type_objects as  $post ) {

			# for post types
			if( 'bbd_pt' == $post->post_type ) {
				$has_post_type = true;
				self::$post_type_ids[] = $post->ID;
				self::$post_types[ $post->ID ] = $post;
			}

			# for taxonomies
			elseif( 'bbd_tax' == $post->post_type ){
				$has_taxonomy = true;
				self::$taxonomy_ids[] = $post->ID;
				 self::$taxonomies[ $post->ID ] = $post;
			}			
		} # end foreach: $posts for post types and taxonomies

		# set the object state for post type and taxonomy existence
		if( ! $has_post_type ) self::$no_post_types = true;
		if( ! $has_taxonomy ) self::$no_taxonomies = true;

		# do nothing further if we don't have a post type or a taxonomy
		if( ! $has_post_type && ! $has_taxonomy ) return;

		/**
		 * Get the post meta that makes the post types and taxonomies work
		 */

		# first, attempt to get the postmeta from the object cache
		$post_meta_data = wp_cache_get( 'bbd_post_types_meta' );

		# if the data wasn't found in the cache, get it from the database
		if( false === $post_meta_data ) {

			# get the relevant values from the database
			$post_meta_query = "SELECT post_id, meta_key, meta_value FROM " . $wpdb->postmeta . 
				" WHERE meta_key LIKE '_bbd_meta_%'";
			$post_meta_data = $wpdb->get_results( $post_meta_query );

			# store the values in the object cache
			if( empty( BBD_Options::$options['disable_cache'] ) ) {
				wp_cache_set( 'bbd_post_types_meta', $post_meta_data );
			}
		}

		# do nothing further if we don't have any meta values for the post types or taxonomies
		if( empty( $post_meta_data ) ) return;

		# parse the post meta data rows
		foreach( $post_meta_data as $field ) {

			# get the simplified key (e.g. `handle` instead of `_bbd_meta_handle`)
			$key = str_replace( '_bbd_meta_', '', $field->meta_key );

			if( ! $key  ) continue;

			# create the ($ID) => (stdClass) entry in self::$meta to hold the field keys and values if it doesn't exist
			if( ! array_key_exists( $field->post_id, self::$meta ) ) self::$meta[ $field->post_id ] = new stdClass();

			# store the field value in self::$meta
			self::$meta[ $field->post_id ]->$key = $field->meta_value;

		} # end foreach: $post_meta_data

	} # end: load_bbd_post_data()


	/**
	 * Return and/or populate self::$post_types array. Executes self::load_bbd_post_data if necessary
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
		self::load_bbd_post_data();
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

		self::load_bbd_post_data();
		return self::$taxonomies;

	} # end: get_taxonomies()


	/**
	 * Return an array of BBD_PT objects for the registered post types
	 *
	 * @return 	array 	May be empty.
	 * @since 	2.0.0
	 */
	public static function get_post_type_objects() {

		$post_type_objects = array();

		# get the active post types
		$post_types = self::get_post_types();
		foreach( $post_types as $post_type ) {
			
			$pt = new BBD_PT( $post_type->ID );
			$post_type_objects[] = $pt;
		}

		return $post_type_objects;

	} # end: get_post_type_objects()

	/**
	 * Return a list of BBD post type names
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
	 * Return an array of BBD_Tax objects for BBD taxonomies
	 *
	 * @return 	array 	May be empty.
	 * @since 	2.0.0
	 */
	public static function get_taxonomy_objects() {

		$taxonomy_objects = array();

		# get the active post types
		$taxonomies = self::get_taxonomies();
		foreach( $taxonomies as $taxonomy ) {
			
			$tax = new BBD_Tax( $taxonomy->ID );
			$taxonomy_objects[] = $tax;
		}

		return $taxonomy_objects;

	} # end: get_taxonomy_objects

	/**
	 * Return an array of BBD taxonomy names
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
		
		BBD::$acf_field_groups = $field_groups;
		return BBD::$acf_field_groups;

	} # end: get_acf_field_groups()

	/**
	 * Load info about the current front end view
	 *
	 * Initializes the following static variables
	 *
	 * - BBD::$view_type
	 * - BBD::$is_bbd (if is_search() is true)
	 * 
	 * @since 	2.0.0
	 */
	public static function load_view_info() {

		# reduce weight for non-plugin views
		if( ! is_search() && ! is_bbd_view() ) return;
		# if we are doing a wp search
		if( is_search() ) { 
			self::$is_bbd = true;
			self::$view_type = 'archive';
			return;
		}

		# if we are doing BBD Search Widget results
		if( ! empty( $_POST['bbd_search'] ) ) {
			self::$view_type = 'bbd-search-results';
		}

		# make sure the BBD post data is loaded
		if( empty( self::$post_type_ids ) || empty( self::$taxonomy_ids ) ) self::load_bbd_post_data();

		# see if there is a queried post type for this view
		if( self::$current_post_type ) {
			if( is_singular() ) self::$view_type = 'single';
			else self::$view_type = 'archive';
		}

	} # end: load_view_info()

	/**
	 * Helper Functions
	 * 
	 * - load_classes()
	 */

	/**
	 * Require the core classes needed whenever the plugin loads
	 *
	 * @since 	2.0.0
	 */ 
	public static function load_classes() {

		# BBD classes
		require_once bbd_dir('/lib/class-bbd-ajax.php');
		require_once bbd_dir('/lib/class-bbd-helper.php');
		require_once bbd_dir('/lib/class-bbd-options.php');
		require_once bbd_dir('/lib/class-bbd-post.php');
		require_once bbd_dir('/lib/class-bbd-pt.php');
		require_once bbd_dir('/lib/class-bbd-tax.php');
		require_once bbd_dir('/lib/class-bbd-field.php');
		require_once bbd_dir('/lib/widgets/class-bbd-search-widget.php');

		# Extended Post Types & Taxonomies
		if( ! function_exists( 'register_extended_post_type' ) ) require_once bbd_dir( '/assets/extended-cpts/extended-cpts.php' );
		if( ! function_exists( 'register_extended_taxonomy' ) ) require_once bbd_dir( '/assets/extended-taxos/extended-taxos.php' );

	} # end: load_classes()

} # end class: BBD