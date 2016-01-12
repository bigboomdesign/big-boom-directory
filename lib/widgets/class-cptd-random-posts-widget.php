<?php
/**
 * The CPTD Random Posts Widget class
 *
 * Handles the backend widget settings form
 * Handles the front end display of the widget
 * Handles the shortcode implementation for `cptd-random-posts`
 *
 * @since 	2.0.0
 */
class CPTD_Random_Posts_Widget extends WP_Widget {

	/**
	 * Class methods
	 * 
	 * - __construct()
	 * - form()
	 * - widget()
	 */

	/**
	 * Construct a new instance
	 * 
	 * @since 	2.0.0
	 */
	public function __construct() {

		$widget_options = array(
			"classname" => "cptd-random-posts-widget",
			"description" => "Display random posts for CPT Directory listings"
		);

		parent::__construct("cptd_random_posts_widget", "CPT Directory Random Posts", $widget_options);

	} # end: __construct()


	/**
	 * Generate HTML for the backend widget settings form
	 *
	 * @param 	array 	$instance 	The current widget settings for this instance
	 * @since 	2.0.0
	 */
	public function form( $instance ) {
	?>
	<div id='cptd-random-posts-form' class='cptd-widget-form' 
		data-widget-id='<?php echo $this->id_base; ?>' 
		data-widget-number='<?php echo $this->number; ?>' 
	>
	<?php
		# show the shortcode for this widget
		if( ! empty( $this->number ) && -1 != $this->number && '__i__' != $this->number ) {
			echo '<p><b>Shortcode:</b><br /><code>[cptd-random-posts widget_id="' . $this->number . '"]</code></p>';
		}

		# Widget title
		?>
		<p><label for='<?php echo $this->get_field_id('title'); ?>'>Title<br />
			<input id='<?php echo $this->get_field_id('title'); ?>' 
				name='<?php echo $this->get_field_name('title'); ?>'
				type='text'
				class='widefat'
				value='<?php echo ! empty( $instance['title'] ) ? $instance['title'] : ''; ?>' 
			/>
		</label></p>
		<?php 
		# Widget Description
		?>
		<p><label for='<?php echo $this->get_field_id('description'); ?>'>Description<br />
			<textarea id='<?php echo $this->get_field_id('description'); ?>' 
				name='<?php echo $this->get_field_name('description'); ?>'
				class='widefat'
			><?php echo ! empty( $instance['description'] ) ? $instance['description'] : ''; ?></textarea>
		</label></p>
		<?php
		# Number of posts
		?>
		<p><label for='<?php echo $this->get_field_id('num_posts'); ?>'>Number of posts to display<br />
			<input id='<?php echo $this->get_field_id('num_posts'); ?>' 
				name='<?php echo $this->get_field_name('num_posts'); ?>'
				type='number'
				class='small-text'
				value='<?php echo ! empty( $instance['num_posts'] ) ? $instance['num_posts'] : 3; ?>' 
			/>
		</label></p>
		<?php

		# Post types
		$post_type_args = array(
			'heading' => '<h4>Post Types</h4>',
			'description' => '<p>Select the post types for the random posts</p>',
			'selected' => ! empty( $instance['post_types'] ) ? $instance['post_types'] : array(),
			'field_id' => $this->get_field_id( 'random_post_type' ),
			'field_name' => $this->get_field_name('post_types'),
			'label_class' => 'post-type-select'
		);
		echo CPTD_Helper::checkboxes_for_post_types( $post_type_args );

		# Taxonomies
		$taxonomy_args = array(
			'heading' => '<h4>Taxonomies</h4>',
			'description' => '<p>Select the taxonomies for the random posts</p>',
			'selected' => ! empty( $instance['taxonomies'] ) ? $instance['taxonomies'] : array(),
			'field_id' => $this->get_field_id( 'random_taxonomy' ),
			'field_name' => $this->get_field_name('taxonomies'),
			'label_class' => 'taxonomy-select'
		);
		echo CPTD_Helper::checkboxes_for_taxonomies( $taxonomy_args );
	?>
	</div><?php // .cptd-widget-form

	} # end: form()


	/**
	 * Generate HTML for the front end widget display
	 *
	 * @param 	array 	$args 		The widget arguments
	 * @param 	array 	$instance 	The widget settings for this instance
	 * @since 	2.0.0
	 */
	public function widget( $args, $instance ) {

		# the ID and number may need to be set if calling from a shortcode
		if( empty( $args['widget_id'] ) ) {
			if( ! empty( $instance['widget_id'] ) ) $args['widget_id'] = $this->id_base . '-' . $instance['widget_id'];
		}
		if( -1 == $this->number ) {
			if( ! empty( $instance['widget_id'] ) ) $this->number = $instance['widget_id'];
		}

		/**
		 * Gather the widget arguments and settings
		 */
		extract( $args, EXTR_SKIP );

		# the widget title
		$title = ( ! empty( $instance['title'] ) ? $instance['title'] : '' );

		# the widget description
		$description = ( ! empty( $instance['description'] ) ? $instance['description'] : '' );

		# the number of posts
		$num_posts = ( ! empty( $instance['num_posts'] ) ? $instance['num_posts'] : 3 );

		# The post types we're drawing random posts from
		$post_types = ( ! empty( $instance['post_types'] ) ? $instance['post_types'] : array() );		

		# The taxonomies (CPTD_Tax post ID) selected by the user for this widget instance
		$taxonomies = ( ! empty( $instance['taxonomies'] ) ? $instance['taxonomies'] : array() );

		# The taxonomy term IDs we are pulling posts from (may be more than needed based on $taxonomies)
		$terms = ( ! empty( $instance['terms'] ) ? $instance['terms'] : array() );

		# Make sure we have at least one post type or one taxonomy
		if( empty( $post_types ) && empty( $taxonomies ) ) return;

		/**
		 * Get the random posts based on widget instance settings
		 */

		# arguments for get_posts
		$random_post_args = array(
			'posts_per_page' => $instance['num_posts'],
			'orderby' 	=> 'rand',
		);

		# add post type names
		if( $post_types ) {

			$post_type_names = array();
			foreach( $post_types as $post_id ) {
				$pt = new CPTD_PT( $post_id );

				if( ! empty( $pt->handle ) ) $post_type_names[] = $pt->handle;
			}
			if( ! empty( $post_type_names )  ) $random_post_args['post_type'] = $post_type_names;
		}

		# build the taxonomy query to add to the main query args
		$tax_query = array();
		if( $taxonomies ) {

			foreach( $taxonomies as $tax_id ) {

				$tax = new CPTD_Tax( $tax_id );
				if( empty( $tax->ID ) ) continue;

				# store the valid WP_Term's here
				$terms_to_query = array();

				# loop through saved term IDs make sure we have integers
				foreach( $terms as $term_id ) {

					$term_id = intval( $term_id );
					if( ! $term_id ) continue;

					# add to the array
					$terms_to_query[] = $term_id;
				}

				# if we have valid terms, add an entry for this taxonomy to $tax_query
				if( ! empty( $terms_to_query ) ) $tax_query[] = array(
					'taxonomy' => $tax->handle,
					'field' => 'term_id',
					'terms' => $terms_to_query,
				);

			} # end foreach: $taxonomies

			# process the tax query if valid terms were found
			if( ! empty( $tax_query ) ) {

				# set the relation
				$tax_query['relation'] = 'OR';

				# add to main query args
				$random_post_args['tax_query'] = $tax_query;
			}
		} # end if: taxonomies are specified

		$posts = get_posts( $random_post_args );

		if( ! $posts ) return;

		/**
		 * Output the widget content
		 */

		echo $before_widget; 
		
		if( $title ) echo $before_title . $title . $after_title; 

		if( $description ) echo '<p>' . $description . '</p>';

		# loop through posts
		if( ! empty( $posts ) ) foreach( $posts as $post ) {
		?>
			<div class='cptd-random-post-container'><a href='<?php echo get_permalink( $post ); ?>'><?php echo $post->post_title; ?></a></div>
		<?php
		} # end foreach: $posts

		echo $after_widget;
		wp_enqueue_style( 'cptd', cptd_url('/css/cptd.css') );

	} # end: widget()

	/**
	 * Helper functions
	 */
	/**
	 * Get the widget settings for a particular widget number
	 *
	 * @param 	(int|string) 	$widget_number		The number for the desired widget (e.g. 6)
	 * @return 	array
	 */
	public function get_instance( $widget_number ) {
		
		# get the settings for this widget (includes all instances)
		$widget_settings_all = $this->get_settings();

		if( empty( $widget_settings_all[ $widget_number ] ) ) return array();
		return $widget_settings_all[ $widget_number ];

	} # end: get_instance()

} # end class: CPTD_Random_Posts_Widget