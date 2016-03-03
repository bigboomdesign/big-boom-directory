<?php
/**
 * Handles front end implementation of custom post type views
 *
 * @since 	2.0.0
 */
class BBD_View {

	/** 
	 * The current front end view type (initialized as BBD::$view_type)
	 *
	 * @param 	string 
	 * @since 	2.0.0
	 */
	var $view_type;

	/**
	 * The current post type being viewed on the front end (may not exist for views like search results)
	 *
	 * @param 	BBD_PT
	 * @since 	2.0.0
	 */
	var $post_type;

	/**
	 * Keeps track of whether fields have been appended to the current post excerpt or content in the loop
	 *
	 * Resets to false on the_post, and then to true after $this->get_acf_html().  For potential future 
	 * functions like a generalized $this->get_fields_html(), we need to make sure to reset the value to true
	 * at the end of the function.
	 *
	 * We need this in order to prevent field sets showing up multiple times for themes that might use
	 * both the_excerpt and the_content in the same view.
	 *
	 * @param 	bool
	 * @since 	2.0.0
	 */
	var $did_post_fields = false;

	/**
	 * The post ID's for this view
	 * 
	 * @param	array
	 * @since 	2.0.0
	 */
	var $post_ids = array();

	/**
	 * The field keys needed for this view
	 *
	 * @param 	array
	 * @since 	2.0.0
	 */
	var $field_keys = array();

	/**
	 * The field objects to display for the current view (BBD_Field objects)
	 * Typically used for views other than single or archive, where ACF fields may not be saved
	 * (e.g. if the view is 'bbd-search-results')
	 *
	 * @param 	array
	 * @since 	2.0.0
	 */
	var $fields = array();

	/**
	 * The saved ACF fields to display for the current view, if any (BBD_Field objects)
	 *
	 * @param 	array
	 * @since 	2.0.0
	 */
	var $acf_fields = array();

	/**
	 * The post meta required for all posts in this view (for single and archive views)
	 * 
	 * @param 	array
	 * @since 	2.0.0
	 */
	var $post_meta = array();

	/**
	 * The image size to use for image fields in this view
	 *
	 * @param 	string
	 * @since 	2.0.0
	 */
	var $image_size = '';

	/**
	 * The image alignment to use for image fields in this view
	 *
	 * @param 	string
	 * @since 	2.0.0
	 */
	var $image_alignment = 'none';

	/** 
	 * Whether or not to auto detect website fields and social media links
	 *
	 * @param 	bool
	 * @since 	2.0.0
	 */
	var $auto_detect_url = false;
	var $auto_detect_social = false;

	/**
	 * The social fields that need to be checked for this view
	 *
	 * @param 	array
	 * @since 	2.0.0
	 */
	var $social_fields_to_check = array();

	/**
	 * The social fields we've completed during the current post
	 *
	 * @param	array
	 * @since 	2.0.0
	 */
	var $completed_social_fields = array();


	/**
	 * Object methods
	 *
	 * 		- __construct()
	 *
	 * 		- load_post_meta()
	 * 		- get_acf_html()
	 * 		- reset_did_post_fields()
	 */

	/**
	 * Construct a new instance
	 *
	 * @since 	2.0.0
	 */
	public function __construct() {

		# make sure we have a valid view
		if( empty( BBD::$view_type ) ) return;

		$this->view_type = BBD::$view_type;

		# check if there is a valid queried post type for the current screen
		if( ! empty( BBD::$current_post_type ) && in_array( BBD::$current_post_type, BBD::$post_type_ids ) ) {

			# load the post type object
			$this->post_type = new BBD_PT( BBD::$current_post_type );

			/**
			 * Load ACF fields
			 */

			# which meta key are we seeking from the post type's WP_Post?
			$meta_field_to_look_for = 'acf_'. $this->view_type .'_fields';

			# see if we have ACF fields set for this view
			if( ! empty( $this->post_type->$meta_field_to_look_for ) ) {
				
				# get the ACF field objects
				$fields = $this->post_type->$meta_field_to_look_for;

				# loop through the field keys (e.g. field_abc123) and store the field data
				foreach( $fields as $field ) {

					$field = new BBD_Field( $field );

					# store the field into the ACF fields array, indexed by order number
					$this->acf_fields[ $field->acf_field['order_no'] ] = $field;

					# store the field key, indexed by the order number
					$this->field_keys[ $field->acf_field['order_no'] ] = $field->key;

					# add field key to social media fields to check, if applicable
					if( $field->is_social_field ) {
						$this->social_fields_to_check[] = $field->key;
					}

				} # end foreach: ACF fields

				# key sort the fields
				ksort( $this->field_keys );
				ksort( $this->acf_fields );

			} # end if: ACF fields are saved for the current screen's post type and view

			 # Set this view's image size based on the view type
			$image_size_key = 'image_size_' . $this->view_type;
			if( isset( $this->post_type->$image_size_key ) ) $this->image_size = $this->post_type->$image_size_key;

			# set the image alignment for the view
			if( empty( $this->post_type->image_alignment ) ) $this->image_alignment = 'none';
			else $this->image_alignment = $this->post_type->image_alignment;

			# if the post type detects URLs, so should this view
			if( $this->post_type->auto_detect_url ) {
				$this->auto_detect_url = true;
			}

			# if the post type detects social media fields, so should this view
			if( $this->post_type->auto_detect_social ) {
				$this->auto_detect_social = true;
			}

		} # end if: current post type is set

		# for search widget results
		if( 'bbd-search-results' == $this->view_type ) {

			# image size: use main options setting as default
			$this->image_size = ! empty( BBD_Options::$options['image_size_archive'] ) ? 
				BBD_Options::$options['image_size_archive'] : 
				'thumbnail';

			$this->image_alignment = ! empty( BBD_Options::$options['image_alignment'] ) ?
				BBD_Options::$options['image_alignment'] :
				'none';

			# auto-detect URLs
			$this->auto_detect_url = ! empty( BBD_Options::$options['auto_detect_url_yes'] );

			# auto-detect social links
			$this->auto_detect_social = ! empty( BBD_Options::$options['auto_detect_social_yes'] );

		} # end if: doing search widget results

	} # end: __construct()

	/**
	 * Load the necessary post meta for each post in this view
	 *
	 * @since 	2.0.0
	 */
	public function load_post_meta() {

		if( ! $this->field_keys ) return;

		global $wpdb;
		global $wp_query;

		if( empty( $wp_query->posts ) )  return;

		foreach( $wp_query->posts as $post ) {
			$this->post_ids[] = $post->ID;
		}

		$meta_query = "SELECT * FROM " . $wpdb->postmeta . 
			" WHERE post_id IN ( ". implode( ', ', $this->post_ids ) ." ) " .
			" AND meta_key IN ( '". implode( "', '", $this->field_keys ) ."' ) ";

		$meta = $wpdb->get_results( $meta_query );

		# loop through post meta results and store the data into $this->post_meta
		foreach( $meta as $row ) {
			if( ! array_key_exists( $row->post_id, $this->post_meta ) ) $this->post_meta[ $row->post_id ] = array();
			$this->post_meta[ $row->post_id ][ $row->meta_key ] = $row->meta_value;
		}
		
	} # end: load_post_meta()


	/**
	 * Return HTML containing this view's ACF field data for a post
	 *
	 * @return 	string 		
	 * @since 	2.0.0
	 */
	public function get_acf_html() {

		ob_start();
		?>
		<div class="bbd-fields-wrap">
		<?php
			# loop through fields for this view
			foreach( $this->acf_fields as $field ) {

				# hookable pre-render action specific to this field name
				do_action( 'bbd_pre_render_field_' . $field->key, $field );

				# print the field HTML
				$field->get_html();

				# hookable post-render action specific to this field name
				do_action( 'bbd_post_render_field_' . $field->key, $field );

			} # end foreach: fields
		?>
		</div>
		<?php

		# get the buffer contents
		$html = ob_get_contents();
		ob_end_clean();

		$this->did_post_fields = true;

		return $html;

	} # end get_acf_html()	

	/**
	 * Reset the 'did post fields' status for the current post in the loop
	 *
	 * Hooks on the_post
	 *
	 * @since 	2.0.0
	 */
	public function reset_did_post_fields() {
		$this->did_post_fields = false;
	}

} # end class: BBD_View