<?php
/**
 * Inserts actions and filters for backend
 * Handles callbacks for actions and filters on backend
 * Produces HTML for backend content on various screens
 * 
 * @since 	2.0.0
 */
class BBD_Admin{

	/**
	 * Insert actions and filters for the backend
	 * @since 2.0.0
	 */ 
	public static function init(){

		# Admin init hook
		add_action( 'admin_init', array( 'BBD_Admin', 'admin_init' ) );

		# Admin menu items
		add_action('admin_menu', array( 'BBD_Admin', 'admin_menu' ), 10 );

		# Admin bar items
		add_action( 'wp_before_admin_bar_render', array( 'BBD_Admin', 'add_view_post_type_to_admin_bar' ) );

		# For add-ons, we want to allow them to go above the 'Information' page
		add_action('admin_menu', array( 'BBD_Admin', 'admin_menu_information' ), 100 );
		
		# Admin scripts and styles
		add_action('admin_enqueue_scripts', array('BBD_Admin', 'admin_enqueue'));
		
		# Inline CSS for all admin page views
		add_action('admin_print_scripts', array('BBD_Admin', 'admin_print_scripts'));
	
		# Action links on main Plugins screen and Network Admin plugins screen
		$plugin = plugin_basename( bbd_dir( '/big-boom-directory.php' ) );
		add_filter( "plugin_action_links_$plugin", array('BBD_Admin', 'plugin_actions') );
		add_filter( "network_admin_plugin_action_links_$plugin", array('BBD_Admin', 'plugin_actions') );

		# Row actions for custom post types
		add_filter( 'post_row_actions', array( 'BBD_Admin', 'post_row_actions' ), 10, 2 );
		add_filter( 'page_row_actions', array( 'BBD_Admin', 'post_row_actions' ), 10, 2 );

		# CMB2 meta boxes
		add_filter( 'cmb2_meta_boxes', array( 'BBD_Meta_Boxes', 'cmb2_meta_boxes' ) );
		add_filter( 'cmb2_render_url_link_texts', array( 'BBD_Meta_Boxes', 'cmb2_render_url_link_texts_callback' ), 10, 5 );
		
		# fix for the URL that cmb2 defines
		add_filter( 'cmb2_meta_box_url', 'update_cmb_meta_box_url' );
		function update_cmb_meta_box_url( $url ) {
		    return bbd_url('/assets/cmb2');
		}

		# advanced custom fields post type names
		add_action( 'acf/get_post_types', array( 'BBD_Admin', 'acf_get_post_types' ) );

	} # end: init()
	
	/**
	 * Callbacks for backend actions and filters
	 * 
	 * - admin_init()
	 * - admin_menu()
	 * - add_view_post_type_to_admin_bar()
	 * - admin_menu_information()
	 * - admin_enqueue()
	 * - admin_print_scripts()
	 * - plugin_actions()
	 * - post_row_actions()
	 * - cmb2_meta_boxes()
	 * - acf_get_post_types()
	 */


	/**
	 * Handler for admin_init hook
	 *
	 * @since	2.0.0
	 */
	public static function admin_init() {

		# register the plugin settings with defaults
		BBD_Options::register_settings();
	}

	/**
	 * Create all admin menu items for the plugin
	 * 
	 * @since 	2.0.0
	 */
	public static function admin_menu(){

		# sub-pages
		add_submenu_page( 'edit.php?post_type=bbd_pt', 'Settings | Big Boom Directory', 'Settings', 'manage_options', 'bbd-settings', array('BBD_Admin', 'settings_page') );

		# Add "Edit Post Type" submenu item for each post type
		foreach( BBD::$post_type_ids as $id ) {

			$pt = new BBD_PT( $id );
			add_submenu_page( 'edit.php?post_type=' . $pt->handle, '', 'Edit Post Type', 'manage_options', 'post.php?post=' . $id .'&action=edit' );
		}
		
		# remove the 'Add New' for post types
		global $submenu;
        unset($submenu['edit.php?post_type=bbd_pt'][10]);

	} # end: admin_menu()

	/**
	 * Add the 'View Post Type' link in the WP Admin Bar when editing a post type 
	 *
	 * @since 	2.0.0
	 */
	public static function add_view_post_type_to_admin_bar() {

		# make sure we only add the link when editing one of our post types
		$screen = get_current_screen();

		if( 'post' == $screen->base && ( $screen->post_type == 'bbd_pt' ) ) {

			# the post being edited
			global $post;

			if( empty( $post->ID ) ) return;

			# get the post type object
			$pt = new BBD_PT( $post->ID );
			if( empty( $pt->slug ) ) return;

			# add item to the admin bar
			global $wp_admin_bar;
			$wp_admin_bar->add_menu( array(
				'parent' => false,
				'id' => 'edit',
				'title' => __('View Post Type'),
				'href' => esc_url( site_url( $pt->slug ) ),
			));

		} # end if: editing a BBD post type

	} # end: add_view_post_type_to_admin_bar()

	public static function admin_menu_information() {
		add_submenu_page( 'edit.php?post_type=bbd_pt', 'Information | Big Boom Directory', 'Information', 'manage_options', 'bbd-information', array('BBD_Admin', 'information_page') );
	} # end: admin_menu_information()
	
	/**
	 * Enqueue admin scripts and styles
	 * 
	 * @since	2.0.0
	 */
	public static function admin_enqueue(){

		$screen = get_current_screen();
		
		wp_register_style( 'bbd-admin', bbd_url('/css/admin/bbd-admin.css') );

		# Plugin Settings
		if( 'bbd_pt_page_bbd-settings' == $screen->base ) {
			wp_enqueue_style( 'bbd-admin' );
		}

		# Widgets Screen / Theme Customizer
		if( 'widgets' == $screen->base || is_customize_preview() ) {
			
			# css
			wp_enqueue_style( 'bbd-admin' );

			# js
			wp_enqueue_script( 'bbd-widgets', bbd_url('/js/admin/bbd-widgets.js'), 
				array('jquery', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable') 
			);
		}

		# Post type edit screen
		if(
			'post' == $screen->base
			&& ( $screen->post_type == 'bbd_pt' || $screen->post_type == 'bbd_tax')
		){
			wp_enqueue_style( 'bbd-admin' );
			wp_enqueue_style('bbd-post-edit-css', bbd_url('/css/admin/bbd-post-edit.css'));
			
			wp_enqueue_script('bbd-post-edit-js', bbd_url('/js/admin/bbd-post-edit.js'), array('jquery'));

			/**
			 * Pass data to the post-edit-js
			 */

			# post ID 
			$post_id = isset( $_GET['post'] ) ? intval( $_GET['post'] ) : 0;

			# reserved post types and taxonomy names are any that are already registered, except the 
			# current post type or taxonomy being edited
			$reserved_handles = array();

			# the current post type or taxonomy being edited
			$current_pt = new BBD_PT( $post_id );

			# loop through existing post types and taxonomies, and load the reserved handles array
			$reserved_candidates = array_merge( 
				get_post_types( '', 'names' ),
				get_taxonomies( '', 'names' )
			);
			$reserved_candidates[] = 'type';

			foreach( $reserved_candidates as $handle_name ) {


				# don't put the current PT name in the restricted list
				if( 
					! empty( $current_pt->handle ) &&
					$current_pt->handle == $handle_name
				) {
					continue;
				}

				$reserved_handles[] = $handle_name;

			} # end foreach: post types

			wp_localize_script( 'bbd-post-edit-js', 'bbdData', array( 
				'post_id' =>  $post_id,
				'reserved_handles' => $reserved_handles,
			) );
		}
			
		# Information screen
		if($screen->base == 'bbd_pt_page_bbd-information'){
			wp_enqueue_style('bbd-readme-css', bbd_url('/css/admin/bbd-readme.css'));
			wp_enqueue_script('bbd-readme-js', bbd_url('/js/admin/bbd-readme.js'), array('jquery'));
		}

	} # end: admin_enqueue()

	/**
	 *
	 */
	public static function admin_print_scripts() {
	?>
	<style>
		.menu-icon-bbd_pt .wp-menu-image {
		    background-image: url( 
		    	<?php echo bbd_url( 'css/admin/big-boom-design-logo.png'); ?>
		    );
		    background-position: center center;
		    background-repeat: no-repeat;
		    background-size: 25px 25px;
		}
		.menu-icon-bbd_pt .wp-menu-image.dashicons-before.dashicons-list-view::before {
			content: '';
		}
	</style>
	<?php
	} # end: admin_print_scripts()
	
	/**
	 * Add action links for this plugin on main Plugins screen (under plugin name)
	 *
	 * @param	array 	$links 	A list of anchor tags pre-pouplated with the WP default plugin links
	 * @return 	array 	The altered $links array 
	 * @since	2.0.0
	 */
	public static function plugin_actions( $links ) {

		/**
		 * Remove the `Edit` link (the last link) if we're on a single site install or on the Network Admin
		 * plugins screen
		 */
		if( ! is_multisite() || is_network_admin() ) array_pop($links);

		# add additional actions for non-network admin plugins screens
		if( ! is_network_admin() ) {

			# Add 'Settings' link to the front
			$settings_link = '<a href="admin.php?page=bbd-settings">Settings</a>';
			array_unshift($links, $settings_link);

			# Add 'Instructions' link to the front
			$instructions_link = '<a href="admin.php?page=bbd-information">Instructions</a>';
			array_unshift($links, $instructions_link);
		}

		return $links;
		
	} # end: plugin_actions()

	/**
	 * Add to the post row actions for custom post types (edit.php)
	 *
	 * @param 	array 		$actions 	The existing array of actions
	 * @param 	WP_Post		$post 		The post for the row whose actions are being edited
	 * @return 	array
	 * @since 	2.0.0
	 */
	public static function post_row_actions( $actions, $post ){

		# make sure we have the post type 'bbd_pt'
		if ( ! ( 'bbd_pt' == $post->post_type ) ) return $actions;
		
		# remove the `Quick Edit` link
		unset( $actions['inline hide-if-no-js'] );

		$pt = new BBD_PT( $post->ID );

		$actions['view_posts'] = '<a href="'. admin_url( 'edit.php?post_type='.$pt->handle ) .'">View Posts</a>';

		return $actions;
	} # end: post_row_actions()

	/** 
	 * Filter the 'name' => 'label' pairs for ACF post type choices
	 * Note we are unsetting 'bbd_pt' and 'bbd_tax' since these are internal post types to the plugin
	 *
	 * @param 	array 	$choices 	The existing 'name' => 'label' pairs
	 * @return 	array
	 * @since 	2.0.0
	 */
	public static function acf_get_post_types( $choices ) {

		# loop through post type 'name' => 'label' pairs
		foreach( $choices as $k => &$v ) {

			# see if the key starts with 'bbd_pt_'
			if( 0 === strpos( $k, 'bbd_pt_' ) ) {

				# get the post type id from the key
				$pt_id = str_replace( 'bbd_pt_', '', $k );

				# get the post type
				$pt = new BBD_PT( $pt_id );
				if( empty( $pt->ID ) ) continue;

				$v = $pt->plural;
			}

			# unset BBD internal post types
			elseif( 'bbd_pt' == $k || 'bbd_tax' == $k ) unset( $choices[ $k ] );
		}  # end foreach: $choices for post types

		return $choices;

	} # acf_get_post_types()

	/**
	 * HTML for admin screens produced by this plugin
	 *
	 * - settings_page()
	 * - information_page()
	 */
	
	/**
	 * Output HTML for the main settings page
	 * 
	 * @since 	2.0.0
	 */
	public static function settings_page(){
		ob_start();
		?>
		<h2>Big Boom Directory Settings</h2>
		<form action="options.php" method="post">
			<?php settings_fields('bbd_options'); ?>
			<?php do_settings_sections('bbd_settings'); ?>
			<?php submit_button(); ?>
		</form>
		<?php
		$html = ob_get_contents();
		ob_end_clean();

		echo self::page_wrap($html);
	} # end: settings_page()
	
	/**
	 * Output HTML for the Information (README.html) page
	 *
	 * @since 	2.0.0
	 */
	public static function information_page(){
		ob_start();
		?>
		<div class='markdown-body'>
			<?php
			require_once bbd_dir('/README.html');
			?>
		</div>
		<?php
		$html = ob_get_contents();
		ob_end_clean();
		echo self::page_wrap($html);
	} # end: information_page()


	/**
	 * Helper Functions for admin area
	 *
	 * - page_wrap()
	 */
	
	/**
	 * Wrap HTML content for a backend screen in a standardized div
	 *
	 * @since 	2.0.0
	 * @param	string 		$s 		The HTML string to wrap in a div
	 * @return 	string		The HTML including the standard wrapper
	 */
	public static function page_wrap($s){
		return "<div class='wrap bbd-admin'>{$s}</div>";
	}
	
} # end: BBD_Admin