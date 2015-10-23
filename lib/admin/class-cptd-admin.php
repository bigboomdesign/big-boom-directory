<?php
/**
 * Inserts actions and filters for backend
 * Handles callbacks for actions and filters on backend
 * Handles meta boxes for backend using CMB2
 * Produces HTML for backend content on various screens
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
		add_action('admin_menu', array( 'CPTD_Admin', 'admin_menu' ) );
		
		# Admin scripts
		add_action('admin_enqueue_scripts', array('CPTD_Admin', 'admin_enqueue'));
	
		# Action links on main Plugins screen
		$plugin = plugin_basename( cptd_dir( '/cpt-directory.php' ) );
		add_filter( "plugin_action_links_$plugin", array('CPTD_Admin', 'plugin_actions') );

		# Row actions for custom post types
		add_filter( 'post_row_actions', array( 'CPTD_Admin', 'post_row_actions' ), 10, 2 );
		add_filter( 'page_row_actions', array( 'CPTD_Admin', 'post_row_actions' ), 10, 2 );

		# CMB2 meta boxes
		add_action( 'cmb2_admin_init', array( 'CPTD_Admin', 'cmb2_meta_boxes' ) );
		
		# fix for the URL that cmb2 defines
		add_filter( 'cmb2_meta_box_url', 'update_cmb_meta_box_url' );
		function update_cmb_meta_box_url( $url ) {
		    return cptd_url('/assets/cmb2');
		}

	} # end: init()
	
	/**
	 * Callbacks for backend actions and filters
	 * 
	 * - admin_init()
	 * - admin_menu()
	 * - admin_enqueue()
	 * - plugin_actions()
	 * - post_row_actions()
	 * - cmb2_meta_boxes()
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
		add_submenu_page( 'edit.php?post_type=cptd_pt', 'Settings | CPT Directory', 'Settings', 'manage_options', 'cptd-settings', array('CPTD_Admin', 'settings_page'));
		add_submenu_page( 'edit.php?post_type=cptd_pt', 'Information | CPT Directory', 'Information', 'manage_options', 'cptd-information', array('CPTD_Admin', 'information_page'));
		
		global $submenu;
        unset($submenu['edit.php?post_type=cptd_pt'][10]);
	} # end: admin_menu()
	
	/**
	 * Enqueue admin scripts and styles
	 * 
	 * @since	2.0.0
	 */
	public static function admin_enqueue(){
		$screen = get_current_screen();

		# Post type edit screen
		if(
			$screen->base == 'post' 
			&& ( $screen->post_type == 'cptd_pt' || $screen->post_type == 'cptd_tax')
		){
			wp_enqueue_style('cptd-post-edit-css', cptd_url('/css/admin/cptd-post-edit.css'));
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
	 * @since	2.0.0
	 * @param	array 	$links 	A list of anchor tags pre-pouplated with the WP default plugin links
	 * @return 	array 	The altered $links array 
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
	 * Set up the meta boxes for the plugin using CMB2
	 *
	 * @since 2.0.0
	 */
	public static function cmb2_meta_boxes() {

		# initialize the settings (they depend on WP data are are not loaded before cmb2_admin_init)
		CPTD_Options::initialize_settings();

		$prefix = '_cptd_meta_';

		/**
		 * Post type meta boxes
		 * 
		 * - Basic post type settings
		 * 		- Name/Handle
		 * 		- Singular
		 * 		- Plural
		 * - Advanced post type settings
		 *		- Slug
		 * 		- Public
		 * 		- Has Archive
		 * 		- Menu Position
		 * 		- Menu Icon
		 *
		 * - Post type fields meta box
		 */

		/**
		 * Basic post type settings meta box
		 */

		# Meta box
		$pt_settings = new_cmb2_box( array(
			'id' 			=> 'cptd_post_type_settings',
			'title'			=> __( 'Post Type Settings', 'cmb2' ),
			'object_types' 	=> array( 'cptd_pt' ),
			'context' 		=> 'normal',
			'priority' 		=> 'high',
		));

		# Basic post type settings fields

		## Singular label
		$pt_settings->add_field( array(
			'name'	=> 'Singular Label',
			'id'	=> $prefix.'singular',
			'type' 	=> 'text',
			'before' => array( 'CPTD_Admin', 'before_label' ),
		));

		## Plural label
		$pt_settings->add_field( array(
			'name' 	=> 'Plural Label',
			'id' 	=> $prefix.'plural',
			'type'	=> 'text',
			'before' => array( 'CPTD_Admin', 'before_label' ),
		));

		## Settings from CPTD core that can be overriden for this post type

		### Auto detect website field
		$website_field = array(
			'id' => $prefix.'auto_detect_url',
			'type' => 'checkbox',
			'name' => 'Auto detect URL fields',
		);
		if( isset( CPTD_Options::$options['auto_detect_url_yes'] ) ) {
			$website_field['default'] = self::default_for_checkbox('on');
		}
		$pt_settings->add_field( $website_field );

		### Auto detect social media
		$social_field = array(
			'id' => $prefix.'auto_detect_social',
			'type' => 'checkbox',
			'name' => 'Auto detect social media fields',
		);

		### default for social media field
		if( isset( CPTD_Options::$options['auto_detect_social_yes'] ) ) {
			$social_field['default'] = self::default_for_checkbox('on');
		}

		$pt_settings->add_field( $social_field );

		### Image size for archives
		$pt_settings->add_field( array(
			'id' => $prefix.'image_size_archive',
			'type' => 'select',
			'default' => CPTD_Options::$options['image_size_archive'],
			'name' => 'Image size for archive views',
			'before' => array('CPTD_Admin', 'before_image_size')
		));

		### Image size for single
		$pt_settings->add_field( array(
			'id' => $prefix.'image_size_single',
			'type' => 'select',
			'default' => CPTD_Options::$options['image_size_single'],
			'name' => 'Image size for single views',
			'before' => array('CPTD_Admin', 'before_image_size')
		));


		/**
		 * Advanced post type settings meta box
		 */

		# Meta box
		$advanced_pt_settings = new_cmb2_box( array(
			'id' 			=> 'cptd_advanced_post_type_settings',
			'title'			=> __( 'Advanced Post Type Settings', 'cmb2' ),
			'object_types' 	=> array( 'cptd_pt' ),
			'context' 		=> 'normal',
			'priority' 		=> 'high',
			'closed'		=> true
		));

		# Advanced post type fields

		## Name/Handle
		$advanced_pt_settings->add_field( array(
			'name' 			=> 'Name <span class="required">*</span>',
			'id'			=> $prefix.'handle',
			'type' 			=> 'text',
			'attributes' 	=> array(
				'readonly' => 'readonly'
			),
			'before' => array( 'CPTD_Admin', 'before_handle' ),
			'sanitization_cb' => array( 'CPTD_Admin', 'sanitize_handle' ),
			'description' 	=> 
				"<div id='handle-container'>
					<a id='change-name'>Change</a>
					<div style='display: none;' id='cancel-name-change'>
						 | <a>Cancel</a>
						 | <a target='_blank' href='https://codex.wordpress.org/Post_Types#Naming_Best_Practices'>More Info</a>
					</div>
					<div id='handle-info' style='display: none;'>
						<p class='description'>The Post Type Name is the most important part of your post type. Once it is set and you have created posts for your post type, this value should not be changed. Don't change this unless you are confident that you know what you are doing.</p>
						<p class='description'>We guessed the ideal Post Type Name based on your title.  If you edit this field, please use only lowercase letters and underscores, and use a singular name like <code>book_review</code> instead of a plural name like <code>book_reviews</code>.</p>
					</div>
				</div>"
		));

		## Slug
		$advanced_pt_settings->add_field( array(
			'name' 			=> 'URL slug',
			'id'			=> $prefix.'slug',
			'type'			=> 'text',
			'attributes'	=> array(
				'readonly' 	=> 'readonly'
			),
			'before'		=> array( 'CPTD_Admin', 'before_slug' ),
			'sanitize_cb' 	=> array( 'CPTD_Admin', 'sanitize_slug' ),
			'description'	=> 
				"<p></p>
				<div id='slug-container'>
					<a id='change-slug'>Change</a>
					<div style='display: none;' id='cancel-slug-change'>
						 | <a>Cancel</a>
					</div>
					<div id='slug-info' style='display: none;'>
						<p class='description'>The slug determines the URL's for your post type's entries. If left empty, we will guess the ideal slug based on your title. For best results, use only lowercase letters and hyphens if you change the slug.</p>
						<p class='description'>For example, <code>book-reviews</code> would produce <code>http://mysite.com/book-reviews</code></p>
						<p class='description'>Once the slug is set and you have created posts for your post type, changing this value can negatively affect your search engine performance and user experience. Don't change this unless you are confident that you know what you are doing.</p>
					</div>
				</div>",
		));

		## Public
		$advanced_pt_settings->add_field( array(
			'name'	=> 'Public',
			'id'	=> $prefix.'public',
			'type' 	=> 'checkbox',
			'default' => self::default_for_checkbox( 'on' )
		));

		## Has Archive
		$advanced_pt_settings->add_field( array(
			'name'		=> 'Has Archive',
			'id'		=> $prefix.'has_archive',
			'type' 		=> 'checkbox',
			'default' => self::default_for_checkbox( 'on' )
		));

		# Menu Position
		$advanced_pt_settings->add_field( array(
			'name' 	=> 'Admin Menu Position',
			'id' 	=> $prefix.'menu_position',
			'type' 	=> 'text_small',
			'description' => '<p><a target="_blank" href="https://codex.wordpress.org/Function_Reference/register_post_type#menu_position">Learn More</a></p>',
		));

		# Menu Icon
		$advanced_pt_settings->add_field( array(
			'name'	=> 'Admin Menu Icon',
			'id'	=> $prefix.'menu_icon',
			'type' 	=> 'text',
			'default' 	=> 'dashicons-admin-post',
			'description'	=> '<a target="_blank" href="https://developer.wordpress.org/resource/dashicons/#admin-post">Learn More</a>'
		));

		/**
		 * Post Type `fields selection` meta box
		 */

		# Meta box
		$pt_fields_select = new_cmb2_box( array(
			'id' 			=> 'cptd_pt_fields_settings',
			'title'			=> __( 'Fields Setup', 'cmb2' ),
			'object_types' 	=> array( 'cptd_pt' ),
			'context' 		=> 'normal',
			'priority' 		=> 'high',
		));

		# Post type archive fields
		$pt_fields_select->add_field(  array(
			'name' => 'Post Type Archive Fields',
			'id' => $prefix.'pt_archive_fields',
			'type' => 'multicheck',
			'attributes' => array( 
				'class' => 'cptd_field_group_select',
				'autocomplete' => 'off'
			),
			'description' => "Select which fields show for your posts on the post type archive page.",
			'before' 	=> array( 'CPTD_Admin', 'before_fields_select'),
			'select_all_button' => false,
			'sanitization_cb' => array( 'CPTD_Admin', 'sanitize_archive_fields' )
		));

		# Post type single fields
		$pt_fields_select->add_field( array(
			'name' => 'Single Post Fields',
			'id' => $prefix.'pt_single_fields',
			'type' => 'multicheck',
			'attributes' => array( 
				'class' => 'cptd_field_group_select',
				'autocomplete' => 'off'
			),
			'description' => 'Select which fields show on the single post view for your post type.',
			'before' 	=> array( 'CPTD_Admin', 'before_fields_select'),
			'select_all_button' => false
		));



		/**
		 * Taxonomy meta boxes
		 * 
		 * - Basic taxonomy settings
		 * 		- Name/Handle
		 * 		- Singular
		 * 		- Plural
		 * 		- Hierarchical
		 * 		- Post Types
		 * - Advanced taxonomy settings
		 *		- Slug
		 * 		- Public
		 */

		/**
		 * Basic taxonomy settings
		 */

		# Meta box
		$tax_settings = new_cmb2_box( array(
			'id' 			=> 'cptd_tax_settings',
			'title'			=> __( 'Taxonomy Settings', 'cmb2' ),
			'object_types' 	=> array( 'cptd_tax' ),
			'context' 		=> 'normal',
			'priority' 		=> 'high',
		));

		# Fields
		
		## Name/Handle
		$tax_settings->add_field( array(
			'name' 			=> 'Name <span class="required">*</span>',
			'id'			=> $prefix.'handle',
			'type' 			=> 'text',
			'attributes' 	=> array(
				'readonly' => 'readonly'
			),
			'before' => array( 'CPTD_Admin', 'before_handle' ),
			'sanitization_cb' => array( 'CPTD_Admin', 'sanitize_handle' ),
			'description' 	=> 
				"<div id='handle-container'>
					<a id='change-name'>Change</a>
					<div style='display: none;' id='cancel-name-change'>
						 | <a>Cancel</a>
						 | <a target='_blank' href='https://codex.wordpress.org/Taxonomies#Registering_a_taxonomy'>More Info</a>
					</div>
					<div id='handle-info' style='display: none;'>
						<p class='description'>The Taxonomy Name is the most important part of your taxonomy. Once it is set and you have created terms and assigned posts for your taxonomy terms, this value should not be changed.</p>
						<p class='description'>We guessed the ideal Taxonomy Name based on your title.  If you edit this field, please use only lowercase letters and underscores, and use a singular name like <code>genre</code> instead of a plural name like <code>genres</code>.</p>
					</div>
				</div>"
		));

		## Singular label
		$tax_settings->add_field( array(
			'name'	=> 'Singular Label',
			'id'	=> $prefix.'singular',
			'type' 	=> 'text',
			'before' => array( 'CPTD_Admin', 'before_label' ),
		));

		## Plural label
		$tax_settings->add_field( array(
			'name' 	=> 'Plural Label',
			'id' 	=> $prefix.'plural',
			'type'	=> 'text',
			'before' => array( 'CPTD_Admin', 'before_label' ),
		));

		## Hierarchical
		$tax_settings->add_field( array(
			'name' 	=> 'Hierarchical',
			'id' 	=> $prefix.'hierarchical',
			'type'	=> 'checkbox',
			'description' => '<p>Leave checked if you want your taxonomy to behave like categories, or uncheck if you want 
				the taxonomy to behave like tags</p>',
			'default' => self::default_for_checkbox( 'on' )
		));

		## Post Types
		$tax_settings->add_field( array(
			'name'	=> 'Post Types',
			'id'	=> $prefix.'post_types',
			'type' 	=> 'multicheck',
			'select_all_button' => false,
			'before'	=> array( 'CPTD_Admin', 'before_tax_post_types' ),
			'description' => "<div id='tax-assign-tip'>
					<p class='description'>It's usually best to assign only <b>one post type per taxonomy</b>. Otherwise, the terms you create will appear under all post types checked.</p>
					<p class='description'>For example, if you have <code>Books</code> and <code>Movies</code> as post types and <code>Genres</code> as a single taxonomy for both post types, you may end up with the term 'Non-Fiction' as an option for both Books and Movies.  In this case, it would probably be best to create two taxonomies, one called <code>Book Genres</code> and another called <code>Movie Genres</code></p>
				</div>"
		));

		/**
		 * Advanced taxonomy settings
		 */

		# Meta box
		$advanced_tax_settings = new_cmb2_box( array(
			'id' 			=> 'cptd_advanced_tax_settings',
			'title'			=> __( 'Advanced Taxonomy Settings', 'cmb2' ),
			'object_types' 	=> array( 'cptd_tax' ),
			'context' 		=> 'normal',
			'priority' 		=> 'high',
			'closed' 		=> true
		));

		# Fields

		## Slug
		$advanced_tax_settings->add_field( array(
			'name' 			=> 'URL slug',
			'id'			=> $prefix.'slug',
			'type'			=> 'text',
			'attributes'	=> array(
				'readonly' 	=> 'readonly'
			),
			'before'		=> array( 'CPTD_Admin', 'before_slug' ),
			'sanitize_cb' 	=> array( 'CPTD_Admin', 'sanitize_slug' ),
			'description'	=> 
				"<p></p>
				<div id='slug-container'>
					<a id='change-slug'>Change</a>
					<div style='display: none;' id='cancel-slug-change'>
						 | <a>Cancel</a>
					</div>
					<div id='slug-info' style='display: none;'>
						<p class='description'>The slug determines the URL's for your taxonomy's term archive pages. If left empty, we will guess the ideal slug based on your title. For best results, use only lowercase letters and hyphens if you change the slug.</p>
						<p class='description'>For example, <code>book-genres</code> would produce <code>http://mysite.com/book-genres/fiction</code> if you had a term called 'Fiction'</p>
						<p class='description'>Once the slug is set and you have created posts for your post type, changing this value can negatively affect your search engine performance and user experience. Don't change this unless you are confident that you know what you are doing.</p>
					</div>
				</div>",
		));

		## Public
		$advanced_tax_settings->add_field( array(
			'name'	=> 'Public',
			'id'	=> $prefix.'public',
			'type' 	=> 'checkbox',
			'default' => self::default_for_checkbox( 'on' )
		));

	} # end: cmb2_meta_boxes()


	/**
	 * Helper functions and hooks for CMB2 meta boxes
	 * 
	 * - default_for_checkbox()
	 * - sanitize_handle()
	 * - before_handle()
	 * - before_label()
	 * - before_image_size()
	 * - before_slug()
	 * - sanitize_slug()
	 * - before_tax_post_types()
	 * - before_fields_select()
	 * - sanitize_archive_fields()
	 */

	/**
	 * Allows checkboxes to have a default value on new post screen
	 * @param 	string 	$default 	The default value to assign
	 * @return 	string 	The default value for new posts, or empty string otherwise
	 * @since 	2.0.0
	 */
	public static function default_for_checkbox( $default ) {
    	return isset( $_GET['post'] ) ? '' : ( $default ? (string) $default : '' );
	}

	/**
	 * Sanitize the user-submitted handle to make sure we only have lowercase and underscores
	 * @param 	string 	$value 	The user-submitted handle
	 * @return	string 	The cleaned handle
	 * @since 	2.0.0
	 */
	public static function sanitize_handle( $value ) {
		return CPTD_Helper::clean_str_for_field( $value );
	}

	/**
	 * Add the default value for post type or taxonomy handle
	 * @param	string	$args 	The arguments for the CMB2 field
	 * @param	string 	$field	The CMB2 field object
	 * @since 	2.0.0
	 */
	public static function before_handle( $args, $field ) {

		global $post;

		# do nothing if value is saved
		if( ! empty( $field->value ) ) return;

		# whether this is a post type (true) or taxonomy (false)
		$bPT = ( 'cptd_pt' == $post->post_type )  ? true : false;

		# the extension for the handle (pt or tax)
		$handle_extension = $bPT ? "pt" : "tax";

		# add the default value
		$field->args['default'] = 'cptd_'. $handle_extension . '_'.$field->object_id;
	}

	/**
	 * Add a helpful description for singular/plural for post types and taxonomies if value is not set
	 *
	 * @param	string	$args 	The arguments for the CMB2 field
	 * @param	string 	$field	The CMB2 field object
	 * @since 	2.0.0
	 */
	public static function before_label( $args, $field ) {

		global $post;

		# do nothing if value is saved
		if( ! empty( $field->value ) ) return;

		# whether this is a post type (true) or taxonomy (false)
		$bPT = ( 'cptd_pt' == $post->post_type )  ? true : false;
		$text = $bPT ? "Book Review" : "Genre";

		$field->args['description'] = 'ex: <code>' . $text .  ( '_cptd_meta_plural' == $args['id'] ? 's' : '') . '</code>';
	}

	/**
	 * Load the default image sizes as choices for the dropdown
 	 *
	 * @param	string	$args 	The arguments for the CMB2 field
	 * @param	string 	$field	The CMB2 field object
	 * @since 	2.0.0
	 */
	public static function before_image_size( $args, $field ) {

		$keys = CPTD_Helper::get_image_sizes();

		foreach( $keys as $key ) {
			$field->args['options'][ $key ] = $key;
		}
	} # end: before_image_size()

	/**
	 * Add a default value for the slug based on the post title
	 *
	 * @param	string	$args 	The arguments for the CMB2 field
	 * @param	string 	$field	The CMB2 field object
	 * @since 	2.0.0
	 */
	public static function before_slug( $args, $field ) {
		global $post;
		$field->args['default'] = CPTD_Helper::clean_str_for_url($post->post_title);
	}

	/**
	 * Sanitize the user-submitted slug to make sure we only have lowercase, underscore and hyphens
	 *
	 * @param	string	$value	The user-submitted slug value
	 * @since 	2.0.0
	 */
	public static function sanitize_slug( $value ) {
		return CPTD_Helper::clean_str_for_url( $value );
	}

	/**
	 * Load post types as options for taxonomy "Post Type" checkboxes
	 *
	 * @param	string	$args 	The arguments for the CMB2 field
	 * @param	string 	$field	The CMB2 field object
	 * @since 	2.0.0
	 */
	public static function before_tax_post_types( $args, $field ) {

		# get all post types		
		$post_types = CPTD::get_post_types();

		# filter out drafts and add to options
		foreach( $post_types as $pt ) {

			if( 'publish' != $pt->post_status ) continue;

			$pt = new CPTD_PT( $pt );
			$field->args['options'][ $pt->ID ] = $pt->plural;

		}

		# if no post types have been created yet
		if( empty( $field->args['options'] ) ){

			$field->args['description'] = "<p>You don't have any published post types yet.  You'll need to <a href='". admin_url('edit.php?post_type=cptd_pt') . "'>create a post type</a> before creating taxonomies.</p>";
			return;
		
		} # end if: no post types
	
	} # end: before_tax_post_types()

	/**
	 * Load post type ACF field group choices
	 *
	 * @param	string	$args 	The arguments for the CMB2 field
	 * @param	string 	$field	The CMB2 field object
	 * @since 	2.0.0
	 */
 	public static function before_fields_select($args, $field) {

 		# get the field groups
 		$field_groups = CPTD::get_acf_field_groups();

 		# loop through ACF field groups
 		if( $field_groups ) {

 			foreach( $field_groups as $group ) {
 				$field->args['options'][ $group->ID ] = $group->post_title;
 			}

	 		# add a container to the description to hold the fields generated by AJAX calls
	 		$field->args['description'] .= "<div id='". $field->args['id'] ."-field-results'></div>";

 		} # end if: field groups exist

 	} # end: before_fields_select()

 	/**
 	 * Adds the fields selected for the chosen field group to the post meta
	 * @param	string	$value	The user-submitted field group ID
	 * @since 	2.0.0
 	 */
 	public static function sanitize_archive_fields( $value ) {

 		# see if the archive fields are set
 		if( isset( $_POST['_cptd_meta_acf_archive_fields'] ) ) {
 			update_post_meta( $_POST['ID'], '_cptd_meta_acf_archive_fields', $_POST['_cptd_meta_acf_archive_fields'] );
 		}
 		# if not, clear out the archive fields post meta
 		else update_post_meta( $_POST['ID'], '_cptd_meta_acf_archive_fields', '' );

 		# see if the single fields are set
 		if( isset( $_POST['_cptd_meta_acf_single_fields'] ) ) {
 			update_post_meta( $_POST['ID'], '_cptd_meta_acf_single_fields', $_POST['_cptd_meta_acf_single_fields'] );
 		}
 		# if not, clear out the single fields post meta
 		else update_post_meta( $_POST['ID'], '_cptd_meta_acf_single_fields', '' );

 		return $value;

 	} # end: sanitize_archive_fields()


	/**
	 * HTML for admin screens produced by this plugin
	 *
	 * - settings_page()
	 * - information_page()
	 */
	
	/**
	 * Output HTML for the main settings page
	 * 
	 * @since 2.0.0
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
	 * @since 2.0.0
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