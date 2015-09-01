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
		
		# Add meta boxes on post edit screens for `cptd_pt` and `cptd_tax` posts
		add_action( 'add_meta_boxes', array('CPTD_Helper', 'add_meta_boxes'), 10, 2);
		add_action( 'save_post', array('CPTD_Helper', 'save_meta_box_data') );
		add_action( 'admin_notices', array('CPTD_Helper', 'post_edit_admin_notices'), 100 );
	}
	
	/**
	 * Create the admin menu items
	 */
	public static function admin_menu(){

		# sub-pages
		add_submenu_page( 'edit.php?post_type=cptd_pt', 'Settings | CPT Directory', 'Settings', 'administrator', 'cptdir-settings', array('CPTD_Admin', 'settings_page'));
		add_submenu_page( 'edit.php?post_type=cptd_pt', 'Information | CPT Directory', 'Information', 'administrator', 'cptdir-information', array('CPTD_Admin', 'information_page'));
		
		global $submenu;
        unset($submenu['edit.php?post_type=cptd_pt'][10]);
	}
	
	/**
	 * Enqueue admin scripts and styles
	 */
	public static function admin_enqueue(){
		$screen = get_current_screen();

		# Post type edit screen
		if(
			$screen->base == 'post' 
			&& ( $screen->post_type == 'cptd_pt' || $screen->post_type == 'cptd_tax')
		){
			wp_enqueue_style('cptd-post-edit-css', cptdir_url('/css/admin/cptd-post-edit.css'));
			wp_enqueue_script('cptd-post-edit-js', cptdir_url('/js/admin/cptd-post-edit.js'), array('jquery'));
		}
			
		# Information screen
		if($screen->base == 'cptd_pt_page_cptdir-information'){
			wp_enqueue_style('cptd-readme-css', cptdir_url('/css/admin/cptd-readme.css'));
			wp_enqueue_script('cptd-readme-js', cptdir_url('/js/admin/cptd-readme.js'), array('jquery'));
		}
	}
	
	/**
	 * Add action links for this plugin on main Plugins screen (under plugin name)
	 */
	public static function plugin_actions($links){
		# remove the `Edit` link
		array_pop($links);
		$settings_link = '<a href="admin.php?page=cptdir-settings">Settings</a>';
		array_unshift($links, $settings_link);
		$instructions_link = '<a href="admin.php?page=cptdir-information">Instructions</a>';
		array_unshift($links, $instructions_link);
		return $links;
	} # end: cptdir_plugin_actions()
	
	/**
	 * Helper Functions
	 */
	
	# default wrapper for HTML
	public static function page_wrap($s){
		return "<div class='wrap cptdir-admin'>{$s}</div>";
	}
	
	/**
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