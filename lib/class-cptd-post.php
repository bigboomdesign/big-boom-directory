<?php
class CPTD_Post{
	var $post 		= ''; # (WP_POST) since we can't extend WP_Post
	var $ID			= 0;  # (int) shortcut for $this->post->ID
	var $post_type 	= ''; # (string) shortcut for $this->post->post_type

	var $fields 	= array(); # (array) holds post meta

	/**
	 * Construct a new instance of CPTD_Post
	 * 
	 * @param (WP_Post|int|string) $post If string or int, we assume it's the ID
	 * @return null
	 */
	function __construct($post){
		# if we're being passed a WP_Post object
		if(is_a($post, 'WP_Post')){
			$this->ID = $post->ID;
			$this->post = $post;
		}

		# if we're being passed an ID as an int or string
		elseif($ID = intval($post)){
			$post = get_post($ID);
			if($post){
				$this->ID = $ID;
				$this->post = $post;
			}
		}
		$this->post_type = $this->post->post_type;

	} # end: __construct()

	/**
	 * Load the core CPTD post meta for this post into $this->meta, adding default values
	 * Does nothing except for for post types `cptd_pt` and `cptd_tax`
	 *
	 * variable of interest: default values core core CPTD meta
	 * 
	 * @type  array $meta {
	 * 		@type string $handle 		The post type or taxonomy name (e.g. 'cptd_pt_101' or 'cptd_tax_102')
	 *		@type string $singular		The post type or taxonomy singular label
	 *		@type string $plural		The post type or taxonomy plural label
	 * } 
	 * @return null
	 */
	public function get_cptd_meta(){

		# Make sure we have the right post type
		if(!in_array($this->post_type, array('cptd_pt', 'cptd_tax'))) return;

		# get the array from the custom field `cptd_post_meta`
		$meta = get_post_meta($this->ID, 'cptd_post_meta', true);
		if(!$meta) $meta = array();

		# don't pass any empty values into our array merge if we're forcing the defaults
		foreach($meta as $k => $v){
			if(!$v) unset($meta[$k]);
		}

		$this->meta = shortcode_atts(
			array(
				'handle' => $this->post->post_type.'_'.$this->ID,
				'singular' => $this->post->post_title,
				'plural' => $this->post->post_title
			),
			$meta,
			'cptd_post_meta'
		);
	} # end: get_cptd_meta()
}