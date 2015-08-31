<?php
class CPTD{
	/*
	 * List of post types (WP_Post objects) created by CPTD
	*/
	static $post_types = array();

	
	/*
	 * Return or populate $this->post_types array
	*/
	public static function get_post_types(){
		if(self::$post_types) return self::$post_types;
		
		# query for the cptd_pt post type
		$post_types = get_posts(array(
			'post_type' 		=> 'cptd_pt',
			'posts_per_page'	=> -1,
			'orderby' 			=> 'post_title',
			'order' 			=> 'ASC'
		));
		foreach($post_types as $post){
			self::$post_types[] = $post;
		}
		return self::$post_types;
	}
	
	/*
	 * Static methods
	*/

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
		self::get_post_types();
		foreach(self::$post_types as $pt){
			# make sure that the post for this post type is published
			if('publish' != $pt->post_status) continue;
			
			$pt = new CPTD_pt($pt);
			$pt->get_cptd_meta();
			self::register_pt($pt);
		}
	} # end: register_post_types()
	
	# Register A Post Type
	# input is filtered by `cptd_register_pt`
	#
	# $pt = array(
	#   'post_type' => (str)
	#   'args' => (array) ... the WP $args array
	#   'names' => (array) ... the Extended CPTs $names array
	# )
	private static function register_pt($pt){
		# Apply a filter that users can hook into
		$pt = apply_filters('cptd_register_pt', 
			array(
				'post_type' => 'cptd_pt_'.$pt->ID,
				'args' => array(),
				'names' => array(
					'singular' => $pt->meta['singular'] ? $pt->meta['singular'] : $pt->post->post_title,
					'plural' => $pt->meta['plural'] ? $pt->meta['plural'] : $pt->post->post_title,
				)
			)
		);
		register_extended_post_type($pt['post_type'], $pt['args'], $pt['names']);
	} # end: register_pt()
	
	
} # end class: CPTD