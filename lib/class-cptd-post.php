<?php
/**
 * A post-like object which is essentially an extension of WP_Post and can itself be extended.
 *
 * Used in favor of WP_Post and `get_post()/get_posts()/get_post_meta()` for better DB performance
 * 
 */ 
class CPTD_Post{

	/**
	 * Class parameters
	 */

	/**
	 * @param  	int 		$ID 			The post ID
	 * @since 	2.0.0
	 */
	var $ID = 0;  # (int) shortcut for $this->post->ID

	/**
	 * @param 	WP_Post		$post 			The WP_Post object being 'extended'
	 * @since 	2.0.0
	 */
	var $post = ''; # (WP_POST) since we can't extend WP_Post

	/**
	 * @param 	int			$post_type 		The post type for the post being extended
	 * @since 	2.0.0
	 */
	var $post_type = '';

	/**
	 * @param 	string 		$post_title		The title of the post being extended
	 * @since 	2.0.0
	 */

	/**
	 * @param 	array 		$fields 		Holds post meta for the post
	 */
	var $fields = array();


	/**
	 * Class methods
	 * 
	 * - __construct()
	 * - load_cptd_meta()
	 */

	/**
	 * Construct a new instance
	 * 
	 * @param (WP_Post|int|string) $post If string or int, we assume it's the ID
	 * @since 2.0.0
	 */
	function __construct( $post = '', $autoload = true ){

		# make sure we have a WP_Post or an ID
		if( ! $post ) return;
		if( ! is_a( $post, 'WP_Post' ) && ! is_string( $post ) && ! is_int( $post ) ) return;

		# if we're being passed a WP_Post object
		if(is_a($post, 'WP_Post')){
			$this->ID = $post->ID;
			$this->post = $post;
			$this->post_type = $post->post_type;
		}

		# if we're being passed an ID as an int or string
		elseif( ( is_string( $post ) || is_int( $post ) ) && $ID = intval($post) ){
			
			$this->ID = $ID;

			# get the post from the DB if we are autoloading
			if( $autoload ) {
				$post = get_post($ID);
				if($post){
					$this->post = $post;
				}
			}
		} # end if: $post was passed as int or string

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
	 * @since 2.0.0
	 */
	public function load_cptd_meta(){

		# Make sure we have the right post type
		if(!in_array($this->post_type, array('cptd_pt', 'cptd_tax'))) return;

		# get the post meta array
		$meta = get_post_meta($this->ID);

		# sanitize and load post meta
		foreach( $meta as $k => $v ) {

			# extract singleton arrays
			if( is_array( $v ) && count( $v ) == 1 ) {
				$v = $v[0];
				$meta[ $k ] = $v;
			}

			# load object parameters for _cptd_meta fields
			if( 0 === strpos( $k, '_cptd_meta_' ) ) {
				$cptd_key = str_replace( '_cptd_meta_', '', $k );
				$this->$cptd_key = $v;
			}

			# Set friendly defaults
			if( ! empty( $this->post->post_title ) ) {
				
				if( ! isset( $this->singular ) ) {
					$this->singular = $this->post->post_title;
				}

				if( ! isset( $this->plural ) ) {
					$this->plural = $this->post->post_title;
				}
			}
		} # end foreach: post meta items

		# store the custom fields while we have them
		$this->fields = $meta;

	} # end: load_cptd_meta()
}