<?php
class CPTD{
	# List of post types (WP_Post objects) created by CPTD
	static $post_types = array();

	# whether we've tried loading post types and found none (to prevent querying again)
	static $bNoPostTypes = false;

	# List of taxonomies (WP_Post objects) created by CPTD
	static $taxonomies = array();

	# whether we've tried loading post types and found none (to prevent querying again)
	static $bNoTaxonomies = false;
	
	/*
	 * Return or populate self::$post_types array
	*/
	public static function get_post_types(){
		if(self::$post_types) return self::$post_types;
		elseif(self::$bNoPostTypes) return array();
		
		# query for the cptd_pt post type
		$post_types = get_posts(array(
			'post_type' 		=> 'cptd_pt',
			'posts_per_page'	=> -1,
			'orderby' 			=> 'post_title',
			'order' 			=> 'ASC'
		));
		if(!$post_types){
			self::$bNoPostTypes = true;
			return array();
		}
		foreach($post_types as $post){
			self::$post_types[] = $post;
		}
		return self::$post_types;
	}

	/*
	 * Return or populate self::$taxonomies array
	*/
	public static function get_taxonomies(){
		if(self::$taxonomies) return self::$taxonomies;
		elseif(self::$bNoTaxonomies) return array();

		# query for the cptd_tax post type
		$taxonomies = get_posts(array(
			'post_type' 		=> 'cptd_tax',
			'posts_per_page' 	=> -1,
			'orderby' 			=> 'post_title',
			'order' 			=> 'ASC'
		));
		# update the model if we didn't find any taxonomies
		if(!$taxonomies){
			self::$bNoTaxonomies = true;
			return array();
		}
		foreach($taxonomies as $tax){
			self::$taxonomies[] = $tax;
		}
		return self::$taxonomies;
	}
} # end class: CPTD