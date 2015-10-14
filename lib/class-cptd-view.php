<?php
/**
 * Handles front end implementation of custom post type views
 */
class CPTD_View {

	/** 
	 * The current front end view type (initialized as CPTD::$view_type)
	 *
	 * @param 	string 
	 * @since 	2.0.0
	 */
	var $view_type;

	/**
	 * The current post type being viewed on the front end
	 *
	 * @param 	CPTD_pt
	 * @since 	2.0.0
	 */
	var $post_type;

	/**
	 * The ACF fields to display for the current view
	 *
	 * @param 	array
	 * @since 	2.0.0
	 */
	var $acf_fields = array();


	/**
	 * Object methods
	 */

	/**
	 * Construct a new instance
	 *
	 * @since 	2.0.0
	 */
	public function __construct() {

		# make sure we have a valid view
		if( empty( CPTD::$view_type ) ) return;

		$this->view_type = CPTD::$view_type;

		# check if there is a valid queried post type for the current screen
		if( ! empty( CPTD::$current_post_type ) && in_array( CPTD::$current_post_type, CPTD::$post_type_ids ) ) {

			# load the post type object
			$this->post_type = new CPTD_pt( CPTD::$current_post_type );

			# Load ACF fields

			## which meta key are we seeking from the post type's WP_Post?
			$meta_field_to_look_for = 'acf_'. $this->view_type .'_fields';

			## see if we have ACF fields set
			if( ! empty( $this->post_type->$meta_field_to_look_for ) ) {
				
				# get the ACF field objects
				$fields = $this->post_type->$meta_field_to_look_for;

				# loop through the field keys (e.g. field_5617329134186) and store field arrays
				foreach( $fields as $field ) {

					$field = get_field_object( $field );

					# make sure the field isn't 'empty' (it may be an array with useles info if no field was found)
					if( '' != $field['name']  ) $this->acf_fields[] = $field;
				}

			} # end if: ACF fields are saved for the current screen's post type and view

		} # end if: current post type is set

	} # end: __construct()

	/**
	 * Return HTML containing this view's ACF field data for a post
	 *
	 * @param 	WP_Post 	$post 	The post to display fields for (default: global $post)
	 * @return 	string 		
	 * @since 	2.0.0
	 */
	public function get_acf_html( $post = '') {

			# Use global $post if it exists
			if( empty( $post ) ) {
				global $post;
				if( empty( $post ) ) return '';
			}

			ob_start();

			# loop through ACF fields
			foreach( $this->acf_fields as $field ) {
			?>
				<p><?php echo $field['label']; ?>: <?php echo get_post_meta( $post->ID, $field['name'], true ); ?></p>
			<?php
			} # end foreach: ACF fields

			# get the buffer contents
			$html = ob_get_contents();
			ob_end_clean();

			return $html;

	} # end get_acf_html()

} # end class: CPTD_View