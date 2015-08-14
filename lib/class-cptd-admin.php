<?php
# a class to handle plugin settings pages
class CPTD_Admin{

	public static function init(){
		# Admin menu items
		add_action('admin_menu', array('CPTD_Admin', 'admin_menu'));
		
		# Admin scripts
		add_action('admin_enqueue_scripts', array('CPTD_Admin', 'admin_enqueue'));
	
		# Action links on main Plugins screen
		$plugin = plugin_basename(__FILE__);
		add_filter("plugin_action_links_$plugin", array('CPTD_Admin', 'plugin_actions'));
	
		# custom actions
		self::add_filters();
		
		# Register post types
		add_action('init', array('CPTD_Admin', 'register_post_types'));
	}
	
	# Create the admin menu items for the plugin
	public static function admin_menu(){
		# top level page
#		add_menu_page('Custom Post Type Directory', 'CPT Directory', 'administrator', 'cptdir-main-page', array('CPTD_Admin', 'main_page'));
		
		# sub-pages
		add_submenu_page( 'edit.php?post_type=cptd_pt', 'Settings | CPT Directory', 'Settings', 'administrator', 'cptdir-settings', array('CPTD_Admin', 'settings_page'));
		add_submenu_page( 'edit.php?post_type=cptd_pt', 'Information | CPT Directory', 'Information', 'administrator', 'cptdir-information', array('CPTD_Admin', 'information_page'));
		
		global $submenu;
        unset($submenu['edit.php?post_type=cptd_pt'][10]);
	}
	
	public static function admin_enqueue(){
		$screen = get_current_screen();

		# Post type edit screen
		if($screen->base == 'post' && $screen->post_type == 'cptd_pt')
			wp_enqueue_style('cptd-admin-css', cptdir_url('/css/admin/cptd-pt-edit.css'));
			
		# Information screen
		if($screen->base == 'cptd_pt_page_cptdir-information'){
			wp_enqueue_style('cptd-readme-css', cptdir_url('/css/admin/cptd-readme.css'));
			wp_enqueue_script('cptd-readme-js', cptdir_url('/js/admin/cptd-readme.js'), array('jquery'));
		}
	}
	
	# Add action links on main Plugins screen
	public static function plugin_actions($links){
		# remove the `Edit` link
		array_pop($links);
		$settings_link = '<a href="admin.php?page=cptdir-settings">Settings</a>';
		array_unshift($links, $settings_link);
		$instructions_link = '<a href="admin.php?page=cptdir-information">Instructions</a>';
		array_unshift($links, $instructions_link);
		return $links;
	} # end: cptdir_plugin_actions()
	
	public static function add_filters(){
		add_filter('cptd_register_pt', array('CPTD_Admin', 'filter_pt'), 1, 1);
	}
	
	# Register all post types
	public static function register_post_types(){
		# Main CPTD post type
		register_extended_post_type('cptd_pt', 
			array(
				'public' => false,
				'show_ui' => true,
				'menu_icon' => 'dashicons-list-view',
				'menu_position' => '30',
				'labels' => array(
					'menu_name' => 'CPT Directory'
				),
			), 
			array(
				'singular' => 'Post Type',
				'plural' => 'Post Types',
			)
		);
		
		# User-defined post types
		$pts = get_posts(
			array(
				'post_type' => 'cptd_pt',
				'posts_per_page' => -1,
				'post_status' => 'publish'
			)
		);
		if($pts)
		foreach($pts as $pt){
			$pt = apply_filters('cptd_register_pt', 
				array(
					'post_type' => $pt->post_name,
					'args' => array(),
					'names' => array()
				)
			);
			self::register_pt($pt);
		}
	} # end: register_post_types()
	
	# register post type
	# input should be filtered `cptd_register_pt`
	# array(
	#   'post_type' => str
	#   'args' => arr
	#   'names' => arr
	# )
	private static function register_pt($pt){
		register_extended_post_type($pt['post_type'], $pt['args'], $pt['names']);
	}
	
	/*
	* Filters
	*/
	
	# post type pre-registration filter
	public static function filter_pt($cpt){
		return $cpt;
	}
	/*
	* Helper Functions
	*/
	
	# default wrapper for HTML
	public static function page_wrap($s){
		return "<div class='wrap cptdir-admin'>{$s}</div>";
	}
	
	/*
	* Admin Pages
	*/
	
	# Main page
	public static function main_page(){
		ob_start();
	?>

	<?php
		$html = ob_get_contents();
		ob_end_clean();
		echo self::page_wrap($html);
	} # end: main_page()
	
	public static function settings_page(){
		ob_start();
		?>
		<h2>Custom Post Type Directory</h2>
		<form action="options.php" method="post">
			<?php settings_fields('cptdir_options'); ?>
			<?php do_settings_sections('cptdir_settings'); ?>
			<?php submit_button(); ?>
		</form>
		<?php
		$html = ob_get_contents();
		ob_end_clean();
		echo self::page_wrap($html);
	} # end: settings_page()
	
	# Information
	public static function information_page(){
		ob_start();
		?>
		<div class='markdown-body'>
			<?php
			require_once cptdir_dir('/README.html');
			?>
		</div>
		<?php
		$html = ob_get_contents();
		ob_end_clean();
		echo self::page_wrap($html);
	} # end: information_page()
	
} # end: CPTD_Admin