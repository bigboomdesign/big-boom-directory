<?php
class CPTD_pt extends CPTD_Post{
	
	var $meta = array(); // the unserialized array from the `cptd_post_meta` custom field for $this->post that CPTD needs to operate

	var $name;
	var $singular;
	var $plural;

	/**
	 * Create a new instance
	 */

	public function __construct($post){
		parent::__construct($post);

		# Load the CPTD post meta
		$this->get_cptd_meta();

		# Set object parameters
		$this->name = $this->meta['handle'];
		$this->singular = $this->meta['singular'];
		$this->plural = $this->meta['plural'];

	} # end: __construct()

	/**
	 * Register the post type associated with this post
	 *
	 * The input array is filtered by `cptd_register_pt` and then passed to `register_extended_post_type()`
	 *
	 * variable of interest:
	 *
	 * @type  array 	$args{
	 *		The arguments that will be filtered by `cptd_register_pt`
	 *
	 *   	@type string 	$post_type 	 	The post type name to register
	 *   	@type array 	$args 			The WP $args array for register_post_type()
	 * 	 	@type array 	$names 			The $names array for register_extended_post_type
	 * }
	 * @return null
	 */

	public function register(){
		$args = array(
			'post_type' => $this->name,
			'args' 		=> array(),
			'names' 	=> array(
				'singular' => $this->singular,
				'plural' => $this->plural
			)
		);
		$args = apply_filters('cptd_register_pt', $args );
		register_extended_post_type($args['post_type'], $args['args'], $args['names']);
	}

} # end: CPTD_pt