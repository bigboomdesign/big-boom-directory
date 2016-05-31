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

		# add Directory shortcodes to WYSIWYG media button row
		/**
		 * clip
		add_action( 'media_buttons', array( 'BBD_Admin', 'media_buttons' ), 100 );
		add_filter( 'mce_external_plugins', array( 'BBD_Admin', 'bbd_add_tinymce' ) );
		*/
	
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
	 * - media_buttons()
	 * - bbd_add_tinymce()
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

			if( ! $pt->public || ! $pt->has_archive ) return;

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

		# Post edit screen (for any post type)
		if( 'post' == $screen->base ) {

			wp_enqueue_style( 'bbd-tinymce', bbd_url( '/css/admin/bbd-tinymce.css' ) );
			wp_enqueue_script( 'bbd-tinymce', bbd_url( '/js/admin/bbd-tinymce.js' ), array( 'jquery' ), time(), false );

			/**
			 * Pass data to the TinyMCE modal for shortcodes
			 *
			 * 		- Button icon URL
			 * 		- Available shortcodes (hookable)
			 * 		- Search widget data
			 * 		- post type data
			 * 		- taxonomy data
			 */
			$data = array();

			# icon for Directory shortcode button
			$data['icon_url'] = bbd_url( '/css/admin/big-boom-design-logo.png');

			# available shortcodes
			$data['shortcodes'] = array(
				array( 'name' => 'bbd-search', 'label' => 'Search Widget' ),
				array( 'name' => 'bbd-a-z-listing', 'label' => 'A-Z Listing' ),
				array( 'name' => 'bbd-terms', 'label' => 'Terms List' ),
			);

			$data['shortcodes'] = apply_filters( 'bbd_shortcodes', $data['shortcodes'] );

			# Search Widget data
			$search_widgets = array();

			$search_widgets_option = get_option( 'widget_bbd_search_widget', array() );
			foreach( $search_widgets_option as $k => $widget_instance ) {

				$k = intval( $k );
				if( ! $k ) continue;

				$search_widgets[] = array(
					'id' => $k,
					'title' => ! empty( $widget_instance['title'] ) ? $widget_instance['title'] : '(no title)',
					'description' => ! empty( $widget_instance['description'] ) ? substr( $widget_instance['description'], 0, 10 ) . '...' : 'No description',
				);
			}

			$data['widget_ids'] = $search_widgets;

			# post type data
			$post_types = bbd_get_post_types();
			foreach( $post_types as $pt ) {
				$data['post_types'][] = array(
					'handle' => $pt->handle,
					'label' => $pt->plural
				);
			}

			# taxonomy data
			$taxonomies = bbd_get_taxonomies();
			foreach( $taxonomies as $tax ) {
				$data['taxonomies'][] = array(
					'handle' => $tax->handle,
					'label' => $tax->plural
				);
			}

			wp_localize_script( 'bbd-tinymce', 'BBD_Shortcode_Data', $data );

		} # end if: post edit screen

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
	 * Print inline CSS to each admin page
	 *
	 * @since	2.1.0
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
	 * clip

	 * Add the 'Directory' button above the WYSIWYG to allow shortcode insertion
	 *
	 * @since 	2.2.0
	 *
	public static function media_buttons() {

		# the CSS to be applied to the <span> element below
		$span_style = 'background-image: url( ' . bbd_url( 'css/admin/big-boom-design-logo.png') . ' );
			background-size: 100% 100%;
			width: 18px;
			height: 18px;
			display: inline-block;
			vertical-align: text-top;';
	?>
		<button id="insert-bbd-shortcode-button" class="button insert-bbd-shortcode" data-editor="content" type="button">
			<span style='<?php echo $span_style; ?>'></span> Directory
		</button>
	<?php
	} # end: media_buttons()

	/**
	 * Add our JS file as a TinyMCE plugin to handle the modal interactions
	 *
	 * @param 	array 	$plugin_array 		The existing array of TinyMCE plugins that we are adding to
	 * @since 	2.2.0
	 *
	public static function bbd_add_tinymce( $plugin_array ) {

	    $plugin_array['bbd_shortcodes'] = bbd_url( '/js/admin/bbd-tinymce.js' );
	    return $plugin_array;

	} # end: bbd_add_tinymce()
	*/
	
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

		# don't do anything if we're viewing posts in the trash
		if( ! empty( $_GET['post_status'] ) && 'trash' == $_GET['post_status'] ) {
			return $actions;
		}

		# make sure we have the post type 'bbd_pt'
		if ( ! ( 'bbd_pt' == $post->post_type ) && ! ( 'bbd_tax' == $post->post_type ) ) return $actions;

		/**
		 * For post types
		 */
		if( 'bbd_pt' == $post->post_type ) {

			# change the "Edit" link to "Edit Post Type"
			$actions['edit'] = '<a href="' . admin_url( 'post.php?post=' . $post->ID . '&action=edit' ) . '">Edit Post Type</a>';
			
			# remove the `Quick Edit` link
			unset( $actions['inline hide-if-no-js'] );

			$pt = new BBD_PT( $post->ID );

			# add a link to Manage Posts
			$actions['manage_posts'] = '<a href="'. admin_url( 'edit.php?post_type='.$pt->handle ) .'">Manage Posts</a>';

			# add a "View Posts" link if the post type is public and has an archive and a slug
			if( $pt->public && $pt->has_archive && ! empty( $pt->slug ) ) {
				$actions['view_posts'] = '<a href="' . site_url( $pt->slug ) . '">View Posts</a>';
			}
		
		} # end if: post type is `bbd_pt`

		/**
		 * For taxonomies
		 */
		elseif( 'bbd_tax' == $post->post_type ) {

			# change the "Edit" link to "Edit Taxonomy"
			$actions['edit'] = '<a href="' . admin_url( 'post.php?post=' . $post->ID . '&action=edit' ) . '">Edit Taxonomy</a>';

			# remove the `Quick Edit` link
			unset( $actions['inline hide-if-no-js'] );

			# get the taxonomy object
			$tax = new BBD_Tax( $post->ID );

			# get the first post type assigned to this taxonomy, so we know where the "Manage Terms" link goes
			if( ! empty( $tax->post_types[0] ) ) {

				$pt = $tax->post_types[0];

				$pt = new BBD_PT( $pt );

				# add a link to Manage Terms
				$actions['manage_terms'] = '<a href="'. admin_url( 'edit-tags.php?taxonomy='. $tax->handle . '&post_type=' . $pt->handle ) .'">Manage Terms</a>';

			}

		} # end if: post type is `bbd_tax`

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