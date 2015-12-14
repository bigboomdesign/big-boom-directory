<?php
/**
 * An object for taxonomies created by the plugin
 *
 * Extension of CPTD_Post.  Contains data necessary for registering and handling custom taxonomies
 *
 * @since 2.0.0
 */

class CPTD_Tax extends CPTD_Post{

	/**
	 * The taxonomy name to be registered
	 *
	 * @param 	string
	 * @since 	2.0.0
	 */
	var $handle;

	/**
	 * The singular label for this taxonomy
	 *
	 * @param 	string
	 * @since 	2.0.0
	 */
	var $singular;

	/**
	 * The plural label for this taxonomy
	 *
	 * @param 	string
	 * @since 	2.0.0
	 */
	var $plural;

	/**
	 * A list of the post type ID's for this taxonomy
	 *
	 * @param 	array
	 * @since 	2.0.0
	 */
	var $post_types = array();

	/**
	 * The URL slug for this post type
	 *
	 * @param 	string
	 * @since 	2.0.0
	 */
	var $slug = '';

	/**
	 * An array of WP_Term objects for this taxonomy
	 *
	 * @param 	array
	 * @since 	2.0.0
	 */
	var $terms = array();

	/**
	 * List of object parameters used for taxonomy registration ( $args for register_taxonomy )
	 *
	 * @param 	array
	 * @since 	2.0.0
	 */
	var $args_settings = array( 'public', 'hierarchical' );

	/**
	 * Whether or not this taxonomy is public
	 *
	 * @param 	bool
	 * @since 	2.0.0
	 */
	var $public;

	/**
	 * Whether this taxonomy is hierarchical
	 *
	 * @param 	bool
	 * @since 	2.0.0
	 */
	var $hierarchical;


	/**
	 * Class methods
	 *
	 * - __construct()
	 * - register()
	 */

	/**
	 * Create a new instance
	 *
	 * @param 	(WP_Post|string|int) 		The post or post_id this object extends
	 * @since 	2.0.0
	 */

	public function __construct( $post ){

		parent::__construct($post);

		if( ! $this->ID ) return;

		$this->load_post_data();
		$this->load_post_meta();

		if( empty( $this->hierarchical ) ) $this->hierarchical = false;
		if( empty( $this->public ) ) $this->public = false;

	} # end: __construct()

	/**
	 * Register the taxonomy associated with this post
	 *
	 * The input array is filtered by `cptd_register_tax` and then passed to `register_extended_taxonomy()`
	 *
	 * variable of interest:
	 *
	 * @type  array 	$args{
	 *		The arguments that will be filtered by `cptd_register_tax`
	 *
	 *   	@type string 		$taxonomy 	 	The taxonomy name to register
	 * 		@type string|array 	$object_type	The post types to associate with this taxonomy
	 *   	@type array 		$args 			The WP $args array for register_taxonomy()
	 * 	 	@type array 		$names 			The $names array for register_extended_taxonomy()
	 * }
	 * @since 2.0.0
	 */

	public function register(){

		# make sure we have a handle set
		if( empty( $this->handle ) ) return;

		# produce an array for the $args['object_type']
		if( empty( $this->post_types ) ) return;

		$object_type = array();
		
		# loop through post ID's which control the post types for this taxonomy
		foreach( $this->post_types as $post_id){

			# make sure we have an acceptable post type
			if( ! in_array( $post_id, CPTD::$post_type_ids ) ) return;
			$pt = new CPTD_PT( $post_id );

			# add the post type name to the list for registration
			$object_type[] = $pt->handle;

		} # end foreach: post types for this taxonomy

		if( empty( $object_type ) ) return;

		$args = array(
			'taxonomy' => $this->handle,
			'object_type' => $object_type,
			'args' => array(),
			'names' => array(
				'singular' 	=> $this->singular,
				'plural' 	=> $this->plural,
			)
		);


		if( ! empty( $this->slug ) ) $args['names']['slug'] = $this->slug;

		# load in any settings from the backend
		foreach( $this->args_settings as $key ) {
			if( ! empty( $this->$key ) || false === $this->$key ) {
				$value = $this->$key;

				# for checkboxes
				if( 'on' == $value ) {
					$value = true;
				}

				# for integers
				if( 'menu_position' == $key ) $value = intval( $value );
				
				# add to args for register_taxonomy
				$args['args'][ $key ] = $value;

				$this->$key = $value;
			}
		}
		
		$args['args']['show_in_nav_menus'] = false;

		# apply filter that user can hook into
		$args = apply_filters('cptd_register_tax', $args);

		# register the taxonomy using Extended Taxonomies
		register_extended_taxonomy( $args['taxonomy'], $args['object_type'], $args['args'], $args['names'] );

	} # end: register()

	/**
	 * Generate a terms dropdown for this taxonomy
	 *
	 * @since 	2.0.0
	 */
	public function get_form_element_html( $setting = array(), $option = '' ) {

		# get the terms if necessary
		if( empty( $this->terms ) ) {

			$terms = get_terms( $this->handle );
			if( ! is_wp_error( $terms ) ) $this->terms = $terms;
		}

		if( empty( $this->terms ) ) return;

		$choices = array();
		$choices[] = array( 'value' => '', 'label' => 'Select' );

		# Loop through terms and load choices for dropdown
		foreach( $this->terms as $term ) {
			$choices[] = array( 'value' => $term->term_id, 'label' => $term->name );
		}
		$setting['choices'] = $choices;

		$setting = CPTD_Helper::get_field_array( $setting );
		?>
		<label for='<?php echo $setting['id']; ?>' ><?php echo $this->plural; ?>
			<?php CPTD_Options::do_settings_field( $setting, $option, $_POST ); ?>
		</label>
		<?php
	} # end: get_form_element_html()

} # end class: CPTD_Tax