<?php
/**
 * Inserts actions and filters for backend
 * Handles callbacks for actions and filters on backend
 * Produces HTML for backend content on various screens
 * 
 * @since 	2.0.0
 */
class CPTD_Admin{

	/**
	 * Insert actions and filters for the backend
	 * @since 2.0.0
	 */ 
	public static function init(){

		# Admin init hook
		add_action( 'admin_init', array( 'CPTD_Admin', 'admin_init' ) );


		# Admin menu items
		add_action('admin_menu', array( 'CPTD_Admin', 'admin_menu' ), 10 );

		# For add-ons, we want to allow them to go above the 'Information' page
		add_action('admin_menu', array( 'CPTD_Admin', 'admin_menu_information' ), 100 );
		
		# Admin scripts and styles
		add_action('admin_enqueue_scripts', array('CPTD_Admin', 'admin_enqueue'));
	
		# Action links on main Plugins screen
		$plugin = plugin_basename( cptd_dir( '/cpt-directory.php' ) );
		add_filter( "plugin_action_links_$plugin", array('CPTD_Admin', 'plugin_actions') );

		# Row actions for custom post types
		add_filter( 'post_row_actions', array( 'CPTD_Admin', 'post_row_actions' ), 10, 2 );
		add_filter( 'page_row_actions', array( 'CPTD_Admin', 'post_row_actions' ), 10, 2 );

		# CMB2 meta boxes
		add_action( 'cmb2_admin_init', array( 'CPTD_Meta_Boxes', 'cmb2_meta_boxes' ) );
		add_filter( 'cmb2_render_url_link_texts', array( 'CPTD_Meta_Boxes', 'cmb2_render_url_link_texts_callback' ), 10, 5 );
		
		# fix for the URL that cmb2 defines
		add_filter( 'cmb2_meta_box_url', 'update_cmb_meta_box_url' );
		function update_cmb_meta_box_url( $url ) {
		    return cptd_url('/assets/cmb2');
		}

		# advanced custom fields post type names
		add_action( 'acf/get_post_types', array( 'CPTD_Admin', 'acf_get_post_types' ) );

	} # end: init()
	
	/**
	 * Callbacks for backend actions and filters
	 * 
	 * - admin_init()
	 * - admin_menu()
	 * - admin_menu_information()
	 * - admin_enqueue()
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
		CPTD_Options::register_settings();
	}

	/**
	 * Create all admin menu items for the plugin
	 * 
	 * @since 	2.0.0
	 */
	public static function admin_menu(){

		# sub-pages
		add_submenu_page( 'edit.php?post_type=cptd_pt', 'Settings | CPT Directory', 'Settings', 'manage_options', 'cptd-settings', array('CPTD_Admin', 'settings_page') );

		# Add "Edit Post Type" submenu item for each post type
		foreach( CPTD::$post_type_ids as $id ) {

			$pt = new CPTD_PT( $id );
			add_submenu_page( 'edit.php?post_type=' . $pt->handle, '', 'Edit Post Type', 'manage_options', 'post.php?post=' . $id .'&action=edit' );
		}
		
		# remove the 'Add New' for post types
		global $submenu;
        unset($submenu['edit.php?post_type=cptd_pt'][10]);

	} # end: admin_menu()

	public static function admin_menu_information() {
		add_submenu_page( 'edit.php?post_type=cptd_pt', 'Information | CPT Directory', 'Information', 'manage_options', 'cptd-information', array('CPTD_Admin', 'information_page') );
	} # end: admin_menu_information()
	
	/**
	 * Enqueue admin scripts and styles
	 * 
	 * @since	2.0.0
	 */
	public static function admin_enqueue(){
		$screen = get_current_screen();
		
		wp_register_script( 'cptd-admin', cptd_url('/js/admin/cptd-admin.js') );
		wp_register_style( 'cptd-admin', cptd_url('/css/admin/cptd-admin.css') );

		# Plugin Settings
		if( 'cptd_pt_page_cptd-settings' == $screen->base ) {
			wp_enqueue_script( 'cptd-admin' );
			wp_enqueue_style( 'cptd-admin' );
		}

		# Widgets Screen
		if( 'widgets' == $screen->base ) {
			wp_enqueue_script( 'cptd-admin' );
			wp_enqueue_style( 'cptd-admin' );
			wp_enqueue_script( 'cptd-widgets', cptd_url('/js/admin/cptd-widgets.js'), array('jquery') );
		}

		# Post type edit screen
		if(
			'post' == $screen->base
			&& ( $screen->post_type == 'cptd_pt' || $screen->post_type == 'cptd_tax')
		){
			wp_enqueue_style('cptd-post-edit-css', cptd_url('/css/admin/cptd-post-edit.css'));
			
			wp_enqueue_script( 'cptd-admin' );
			wp_enqueue_script('cptd-post-edit-js', cptd_url('/js/admin/cptd-post-edit.js'), array('jquery'));

			# pass the post ID to the post-edit-js
			$post_id = isset( $_GET['post'] ) ? $_GET['post'] : 0;
			wp_localize_script( 'cptd-post-edit-js', 'cptdData', array( 'postId' =>  $post_id ) );
		}
			
		# Information screen
		if($screen->base == 'cptd_pt_page_cptd-information'){
			wp_enqueue_style('cptd-readme-css', cptd_url('/css/admin/cptd-readme.css'));
			wp_enqueue_script('cptd-readme-js', cptd_url('/js/admin/cptd-readme.js'), array('jquery'));
		}

	} # end: admin_enqueue()
	
	/**
	 * Add action links for this plugin on main Plugins screen (under plugin name)
	 *
	 * @param	array 	$links 	A list of anchor tags pre-pouplated with the WP default plugin links
	 * @return 	array 	The altered $links array 
	 * @since	2.0.0
	 */
	public static function plugin_actions($links){

		# remove the `Edit` link
		array_pop($links);

		# Add 'Settings' link to the front
		$settings_link = '<a href="admin.php?page=cptd-settings">Settings</a>';
		array_unshift($links, $settings_link);

		# Add 'Instructions' link to the front
		$instructions_link = '<a href="admin.php?page=cptd-information">Instructions</a>';
		array_unshift($links, $instructions_link);

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

		# make sure we have the post type 'cptd_pt'
		if ( ! ( 'cptd_pt' == $post->post_type ) ) return $actions;
		
		# remove the `Quick Edit` link
		unset( $actions['inline hide-if-no-js'] );

		$pt = new CPTD_PT( $post->ID );

		$actions['view_posts'] = '<a href="'. admin_url( 'edit.php?post_type='.$pt->handle ) .'">View Posts</a>';

		return $actions;
	} # end: post_row_actions()

	/** 
	 * Filter the 'name' => 'label' pairs for ACF post type choices
	 * Note we are unsetting 'cptd_pt' and 'cptd_tax' since these are internal post types to the plugin
	 *
	 * @param 	array 	$choices 	The existing 'name' => 'label' pairs
	 * @return 	array
	 * @since 	2.0.0
	 */
	public static function acf_get_post_types( $choices ) {

		# loop through post type 'name' => 'label' pairs
		foreach( $choices as $k => &$v ) {

			# see if the key starts with 'cptd_pt_'
			if( 0 === strpos( $k, 'cptd_pt_' ) ) {

				# get the post type id from the key
				$pt_id = str_replace( 'cptd_pt_', '', $k );

				# get the post type
				$pt = new CPTD_PT( $pt_id );
				if( empty( $pt->ID ) ) continue;

				$v = $pt->plural;
			}

			# unset CPTD internal post types
			elseif( 'cptd_pt' == $k || 'cptd_tax' == $k ) unset( $choices[ $k ] );
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
		<h2>CPT Directory Settings</h2>
		<form action="options.php" method="post">
			<?php settings_fields('cptd_options'); ?>
			<?php do_settings_sections('cptd_settings'); ?>
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
			require_once cptd_dir('/README.html');
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
		return "<div class='wrap cptd-admin'>{$s}</div>";
	}
	
} # end: CPTD_Admin