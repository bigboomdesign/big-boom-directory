<?php
/**
 * A post-like object which is essentially an extension of WP_Post and can itself be extended.
 *
 * Used in favor of WP_Post and `get_post()/get_posts()/get_post_meta()` for better DB performance
 *
 * @since 	2.0.0
 */ 
class BBD_Post{

	/**
	 * Class parameters
	 */

	/**
	 * The WP_Post object being 'extended'
	 *
	 * @param 	WP_Post	
	 * @since 	2.0.0
	 */
	var $post = '';

	/**
	 * The post ID
	 *
	 * @param  	int
	 * @since 	2.0.0
	 */
	var $ID = 0;

	/**
	 * The post type for the post being extended
	 *
	 * @param 	string
	 * @since 	2.0.0
	 */
	var $post_type = '';

	/**
	 * The title of the post 
	 * 
	 * @param 	string 
	 * @since 	2.0.0
	 */	
	var $post_title;

	/**
	 * The post content
	 *
	 * @param 	string 
	 * @since 	2.0.0
	 */	
	var $post_content;

	/**
	 * The status of the post
	 *
	 * @param 	string 
	 * @since 	2.0.0
	 */
	var $post_status = '';

	/**
	 * The post meta for the post
	 * 
	 * @param 	array 
	 */
	var $fields = array();


	/**
	 * Class methods
	 * 
	 * - __construct()
	 * - load_bbd_meta()
	 */

	/**
	 * Construct a new instance
	 * 
	 * @param 	(WP_Post|stdObject|int|string) 	$post 		The object we are extending. If string or int, we assume it's the ID
	 * @param	bool 							$autoload	If $post is an ID, $autoload determines whether to autoload $this->post using get_post()
	 * @since 	2.0.0
	 */
	function __construct( $post = '', $autoload = false ){

		# make sure we have a WP_Post, stdClass object, or an ID
		if( ! $post ) return;
		if( ! is_a( $post, 'WP_Post' ) && ! is_a( $post, 'stdClass' ) && ! is_string( $post ) && ! is_int( $post ) ) {
			return;
		}

		# if we're being passed a WP_Post object
		if(is_a($post, 'WP_Post')){
			$this->ID = $post->ID;
			$this->post = $post;
			$this->post_type = $post->post_type;
		}

		# if we're being passed an stdClass object
		elseif( is_a( $post, 'stdClass' ) ) {

			if( empty( $post->ID ) ) return;

			foreach( get_object_vars( $post ) as $k => $v ) {
				$this->$k = $v;
			}
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

		# load the post type parameter if it's not set and we have a WP_Post
		if( empty( $this->post_type ) && ! empty( $this->post ) ) $this->post_type = $this->post->post_type;

	} # end: __construct()


	/**
	 * Methods specific to BBD_PT and BBD_Tax and common to both
	 * 
	 * - load_post_data()
	 * - load_post_meta()
	 */

	/**
	 * Use $this->ID to load post data for this instance from BBD::$post_types and BBD::$taxonomies
	 * 
	 * @since 	2.0.0
	 */
	public function load_post_data() {

		$post = '';

		# get the post data 

		## first check BBD::$post_types
		if( array_key_exists( $this->ID, BBD::$post_types ) ) {

			$post = BBD::$post_types[ $this->ID ];
		
		} # end if: post is a post type

		## then check BBD::$taxonomies
		elseif( array_key_exists( $this->ID, BBD::$taxonomies ) ) {
			$post = BBD::$taxonomies[ $this->ID ];
		}

		else return;

		# load post parameters
		if( $post ) {
			foreach( get_object_vars( $post ) as $key => $value ) {
				$this->$key = maybe_unserialize( $value );
			}
		}
	} # end: load_post_data()

	/**
	 * Use $this->ID to load BBD meta values for this instance from BBD::$meta
	 *
	 * @since 	2.0.0
	 */
	public function load_post_meta() {

		# get the field data from BBD::$meta
		if( array_key_exists( $this->ID, BBD::$meta ) ) {

			$meta = BBD::$meta[ $this->ID ];

			foreach( get_object_vars( $meta ) as $key => $value ) {
				$this->$key = maybe_unserialize( $value );
			}
		
		} # end if: post meta exists

		# Use post title for default labels
		if( ( empty( $this->singular ) || empty( $this->plural ) ) && ! empty( $this->post_title )  ) {
			if( empty( $this->singular ) ) $this->singular = $this->post_title;
			if( empty( $this->plural ) ) $this->plural = $this->post_title;
		}
	} # end: load_post_meta()

} # end class: BBD_Post