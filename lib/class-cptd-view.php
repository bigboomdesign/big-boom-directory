<?php
/**
 * Handles front end implementation of custom post type views
 *
 * @since 	2.0.0
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
	 * @param 	CPTD_PT
	 * @since 	2.0.0
	 */
	var $post_type;

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
	 * The post meta required for all posts in this view
	 * 
	 * @param 	array
	 * @since 	2.0.0
	 */
	var $post_meta = array();

	/**
	 * The ACF fields to display for the current view (CPTD_Field objects)
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
			$this->post_type = new CPTD_PT( CPTD::$current_post_type );

			# Load ACF fields

			## which meta key are we seeking from the post type's WP_Post?
			$meta_field_to_look_for = 'acf_'. $this->view_type .'_fields';

			## see if we have ACF fields set for this view
			if( ! empty( $this->post_type->$meta_field_to_look_for ) ) {
				
				# get the ACF field objects
				$fields = $this->post_type->$meta_field_to_look_for;

				# loop through the field keys (e.g. field_abc123) and store the field data
				foreach( $fields as $field ) {

					$field = new CPTD_Field( $field );
					$this->field_keys[] = $field->key;
					if( $field->is_acf ) $this->acf_fields[] = $field;
				}

			} # end if: ACF fields are saved for the current screen's post type and view

		} # end if: current post type is set

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
		<div class="cptd-fields-wrap">
		<?php
			# loop through fields for this view
			foreach( $this->acf_fields as $field ) {

				# hookable pre-render action specific to this field name
				do_action( 'cptd_pre_render_field_' . $field->key, $field );

				# print the field HTML
				$field->get_html( true );

				# hookable post-render action specific to this field name
				do_action( 'cptd_post_render_field_' . $field->key, $field );

			} # end foreach: fields
		?>
		</div>
		<?php

		# get the buffer contents
		$html = ob_get_contents();
		ob_end_clean();

		return $html;

	} # end get_acf_html()	

} # end class: CPTD_View