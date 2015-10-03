<?php
class CPTD_pt extends CPTD_Post{
	
	var $handle;
	var $singular;
	var $plural;

	var $slug;
	var $public;
	var $has_archive;
	var $menu_position;
	var $menu_icon;

	/**
	 * Arguments for post registration
	 */
	var $args_settings = array( 'public', 'has_archive', 'menu_position', 'menu_icon' );

	/**
	 * Create a new instance
	 */

	public function __construct($post){
		parent::__construct($post);

		# Load the CPTD post meta
		$this->load_cptd_meta();

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

		# make sure we have the handle set
		if( empty( $this->handle ) ) return;
		
		$args = array(
			'post_type' => $this->handle,
			'args' 		=> array(

			),
			'names' 	=> array(
				'singular' => $this->singular,
				'plural' => $this->plural,
			)
		);

		if( ! empty( $this->slug ) ) $args['names']['slug'] = $this->slug;

		# load in any settings from the backend
		foreach( $this->args_settings as $key ) {
			if( ! empty( $this->$key ) ) {
				$value = $this->$key;

				# for checkboxes
				if( 'on' == $value ) {
					$value = true;
				}

				# for integers
				if( 'menu_position' == $key ) $value = intval( $value );
				$args['args'][ $key ] = $value;

				$this->$key = $value;
			}
		}

		$args = apply_filters('cptd_register_pt', $args );
		register_extended_post_type($args['post_type'], $args['args'], $args['names']);
	}

} # end: CPTD_pt