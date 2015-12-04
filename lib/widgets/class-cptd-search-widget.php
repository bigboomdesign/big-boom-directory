<?php
/**
 * The CPT Search Widget class
 *
 * Handles the backend widget settings form
 * Handles the front end display of the widget
 *
 * @since 	2.0.0
 */
class CPTD_Search_Widget extends WP_Widget{

	/**
	 * The existing meta keys for all CPTD posts, in alphabetical order
	 * These are the available search filters for the widget
	 *
	 * @param 	array
	 * @since 	2.0.0
	 */
	var $field_keys = array();

	/**
	 * Class methods
	 *
	 * - __construct()
	 */

	/**
	 * Construct a new instance
	 * 
	 * @since 	2.0.0
	 */
	function __construct(){

		$widget_options = array(
			"classname" => "cptd-search-widget",
			"description" => "Advanced search widget for CPT Directory listings"
		);

		parent::__construct("cptd_search_widget", "CPT Directory Search", $widget_options);
		$this->field_keys = CPTD_Helper::get_all_field_keys();

	} # end: __construct()

	/**
	 * Generate HTML for the backend widget settings form
	 *
	 * @param 	array 	$instance 	The current widget settings for this instance
	 * @since 	2.0.0
	 */
	function form( $instance ) {
	?>
	<div class='cptd-search-widget-form'>
	<?php
		# the widget title ?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>">
		Title: 
		<input type="text" class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php if( ! empty( $instance['title'] ) ) echo esc_attr( $instance['title'] ); ?>"/>
		</label></p>

		<?php # The View All link ?>
		<p><label for="<?php echo $this->get_field_id('view_all_link'); ?>">
		"View All" link:
		<input type="text" class="widefat" id="<?php echo $this->get_field_id('view_all_link'); ?>" name="<?php echo $this->get_field_name('view_all_link'); ?>" value="<?php if( ! empty( $instance['view_all_link'] ) ) echo esc_attr($instance['view_all_link']); ?>"/>
		</label></p>	

		<?php # the View All link text ?>
		<p><label for="<?php echo $this->get_field_id('view_all'); ?>">
		Text for "View All" link:
		<input type="text" class="widefat" id="<?php echo $this->get_field_id('view_all'); ?>" name="<?php echo $this->get_field_name('view_all'); ?>" value="<?php if( ! empty( $instance['view_all'] ) ) echo esc_attr($instance['view_all']); ?>"/>
		</label></p>	

		<?php # The description field ?>
		<p><label for="<?php echo $this->get_field_id('description'); ?>"/>
		Description: 
		<textarea class="widefat" id="<?php echo $this->get_field_id('description'); ?>" name="<?php echo $this->get_field_name('description'); ?>"><?php if( ! empty( $instance['description'] ) ) echo esc_attr($instance['description']); ?></textarea>
		</label></p>
		
		<?php # The `submit_text` option ?>
		<p><label for="<?php echo $this->get_field_id('submit_text'); ?>">
		Text for Submit button:
		<input type="text" class="widefat" id="<?php echo $this->get_field_id('submit_text'); ?>" name="<?php echo $this->get_field_name('submit_text'); ?>" value="<?php if( ! empty( $instance['submit_text'] ) ) echo esc_attr( $instance['submit_text'] ); ?>"/>
		</label></p>

		<?php # The search results page ?>
		<p><label for='<?php echo $this->get_field_id('results_page'); ?>'>
		Search Results Page:<br />
		<?php
			$search_page = ! empty( $instance['search_page'] )  ? 
				$instance['search_page'] :
				(
					! empty( CPTD_Options::$options['search_page'] ) ?
						CPTD_Options::$options['search_page'] : 
						''
				);

			wp_dropdown_pages( array( 
				'name' => $this->get_field_name('search_page'), '
				selected' => $search_page 
			));
		?>
		</label></p>

		<?php 

		# Checkboxes to select post types
		if( ! empty( CPTD::$post_types ) ) {

			# the current value for this widget instance
			$post_types = ! empty( $instance['post_types'] ) ? $instance['post_types'] : array();
		?>
			<h4>Post Types</h4>
			<p>Choose which post types should be searched by the widget</p>
		<?php

			foreach( CPTD::$post_types as $post_type ) {

				$pt = new CPTD_PT( $post_type->ID );
			?>
				<label for='<?php echo $this->get_field_id('post_type_' . $pt->ID ); ?>' class='post-type-select'>
					<input id='<?php echo $this->get_field_id('post_type_' . $pt->ID ); ?>'
						type='checkbox'
						name='<?php echo $this->get_field_name( 'post_types' ); ?>[]'
						value='<?php echo $pt->ID; ?>'
						<?php checked( true, in_array( $pt->ID, $post_types ) ); ?>
					/> <?php echo $pt->plural; ?>
				</label>
			<?php
			} # end foreach: registered post types

		} # end if: post types exist

		# Checkboxes to select taxonomies
		if( ! empty( CPTD::$taxonomies ) ) {

			# the current value for this widget instance
			$taxonomies = ! empty( $instance['taxonomies'] ) ? $instance['taxonomies'] : array();
		?>
			<h4>Taxonomies</h4>
			<p>Select the taxonomies you'd like to use as search filters</p>
		<?php
			# loop throught the registered CPTD taxonomies
			foreach( CPTD::$taxonomies as $tax ) {
				$tax = new CPTD_Tax( $tax->ID );
			?>
				<label for='<?php echo $this->get_field_id( 'taxonomy_' . $tax->ID ); ?>' class='taxonomy-select'>
					<input id='<?php echo $this->get_field_id( 'taxonomy_' . $tax->ID ); ?>' 
						type='checkbox' 
						name='<?php echo $this->get_field_name( 'taxonomies' ); ?>[]' 
						value='<?php echo $tax->ID; ?>'
						<?php echo checked( true, in_array( $tax->ID, $taxonomies ) ); ?>
					 /> <?php echo $tax->plural; ?>
				</label>
			<?php
			} # end foreach: registered taxonomies

		} # end if: taxonomies exist
		?>

		<?php 
		# Checkboxes to select custom fields and field options 
		if( ! empty( $this->field_keys ) ) {
		?>
			<h4>Fields</h4>
			<p>Select the fields you'd like to use as search filters</p>
			<?php

			# loop through custom fields and display checkboxes and options area for each field
			foreach( $this->field_keys as $field ) {

				$field = new CPTD_Field( $field );
			?>
			<div class='cptd-search-widget-field'>

				<?php # The main field checkbox ?>
				<label for="<?php echo $this->get_field_id('meta_keys[' . $field->key . ']'); ?>">
					<input type="checkbox" name="<?php echo $this->get_field_name('meta_keys'); ?>[]" id="<?php echo $this->get_field_id('meta_keys[' . $field->key . ']'); ?>" value="<?php echo $field->key; ?>" 
						<?php
							if( ! empty( $instance['meta_keys'] ) && is_array( $instance['meta_keys'] ) ) foreach ($instance['meta_keys'] as $f ) { checked( $f , $field->key );  }
						?>
					/><?php echo $field->label; ?>
				</label>

				<?php # Radio buttons for the different field types ?>
				<div class='field-type-select' style='display: none;'>
					<h5>Filter type</h5>
					<?php 
						# generate a key for this widget option
						$field_type_key = $field->key . '_field_type'; 

					# The `text` option
					?>
					<label for='<?php echo $this->get_field_id( $field_type_key . '_text' ); ?>' >
						<input id= '<?php echo $this->get_field_id( $field_type_key . '_text'); ?>' 
							type='radio' 
							value='text' 
							name='<?php echo $this->get_field_name(  $field_type_key ); ?>' 
							<?php if( ! empty( $instance[ $field_type_key ] ) ) echo checked( $instance[ $field_type_key ], 'text' ); ?>
						/> Text
					</label>

					<?php # The `select` option ?>
					<label for='<?php echo $this->get_field_id( $field_type_key . '_select' ); ?>' >
						<input id= '<?php echo $this->get_field_id( $field_type_key . '_select' ); ?>' 
							type='radio' 
							value='select' 
							name='<?php echo $this->get_field_name(  $field_type_key ); ?>' 
							<?php if( ! empty( $instance[ $field_type_key ] ) ) echo checked( $instance[ $field_type_key ], 'select' ); ?>
						/> Select
					</label>

					<?php # The `checkbox` option ?>
					<label for='<?php echo $this->get_field_id( $field_type_key . '_checkbox' ); ?>' >
						<input id= '<?php echo $this->get_field_id( $field_type_key . '_checkbox' ); ?>' 
							type='radio' 
							value='checkbox' 
							name='<?php echo $this->get_field_name(  $field_type_key ); ?>' 
							<?php if( ! empty( $instance[ $field_type_key ] ) ) echo checked( $instance[ $field_type_key ], 'checkbox' ); ?>
						/> Checkboxes
					</label>

				</div><?php // .field-type-select ?>
			</div><?php // .cptd-search-widget-field ?>
			<?php

			} # end foreach: $this->field_keys
		} # end if: field keys exist
	?>
	</div><?php // .cptd-search-widget-form ?>
	<?php
	} # end: form()

	/**
	 * Generate HTML for the front end widget display
	 *
	 * @param 	array 	$args 		The widget arguments
	 * @param 	array 	$instance 	The widget settings for this instance
	 * @since 	2.0.0
	 */
	function widget( $args, $instance ) {

		/**
		 * Gather the widget arguments and settings
		 */
		extract( $args, EXTR_SKIP );
	
		$search_page = ! empty( $instance['search_page'] ) ?
			$instance['search_page'] :
			(
				! empty( CPTD_Options::$options['search_page'] ) ?
					CPTD_Options::$options['search_page'] :
					''
			);

		# the widget title
		$title = ( ! empty( $instance['title'] ) ? $instance['title'] : '' );

		# The 'View All' text
		$view_all = ( ! empty( $instance['view_all'] ) ? $instance['view_all'] : '' );

		# The 'View All' URL
		$view_all_link = ( ! empty( $instance['view_all_link'] ) ? 
			$instance['view_all_link'] : 
			( ! empty( CPTD_Options::$options['search_page'] ) ? 
				get_permalink( CPTD_Options::$options['search_page'] ) : 
				""
			)
		);

		# The content to print above the search widget
		$description = ( ! empty( $instance['description'] ) ? $instance['description'] : '' );

		# The post types we're using for the search
		$post_types = ( ! empty( $instance['post_types'] ) ? $instance['post_types'] : array() );

		# The taxonomies we're using as filters
		$taxonomies = ( ! empty( $instance['taxonomies'] ) ? $instance['taxonomies'] : array() );

		# The meta keys we are using as filters
		$meta_keys = ( ! empty( $instance['meta_keys'] ) ? $instance['meta_keys'] : array() );

		# The text for the submit button
		$submit_text = ( ! empty( $instance['submit_text'] ) ? $instance['submit_text'] : 'Search' );


		/**
		 * Output the widget content
		 */
		echo $before_widget; 
		
		if( $title ) echo $before_title . $title . $after_title; 

		if( $view_all && $view_all_link ) echo "<p class='cptd-view-all'><a href='" . $view_all_link . "'>" . $view_all . "</a></p>";
		if( $description ) echo"<p>". $description . "</p>";		
		?>
			<form method="post" 
				id="cptd-search-form" 
				action="<?php if( ! empty( $search_page ) ) echo get_permalink( $search_page ); ?>"
			><?php

				# Taxonomy filters 
				foreach( $taxonomies as $tax_id ) {

					$tax = new CPTD_Tax( $tax_id );

					$setting = array(
						'id' => 'taxonomy_' . $tax_id,
						'label' => $tax->singular,
						'type' => 'select',
					);
					?>
					<div class='cptd-search-filter'>
						<?php $tax->get_form_element_html( $setting, 'cptd_search', $_POST ); ?>
					</div>
					<?php

				} # end foreach: $taxonomies

				# Loop through selected filters
				if( ! empty( $meta_keys ) ) 
				foreach( $meta_keys as $meta_key ) {

					$field = new CPTD_Field( $meta_key );

					# get the field type, using text as default
					$field_type = ! empty( $instance[ $meta_key . '_field_type' ] ) ?
						$instance[ $meta_key . '_field_type' ] :
						'text';

					# display the form field element
					$setting = array(
						'id' => $meta_key,
						'type' => $field_type,
					);
					?>
					<div class='cptd-search-filter'>
						<?php $field->get_form_element_html( $setting ); ?>
					</div>
					<?php

				} # end foreach: $this->fields

				# Add hidden input to keep track of the post types
				?>
				<input type='hidden' 
					name='cptd_search[post_types]'
					value='<?php echo implode( ',', $post_types ); ?>'
				/>
				<?php
				# Add hidden input to keep track of Widget ID
				?>
				<input type="hidden" 
					name="cptd_search[widget_id]" 
					value="<?php echo isset( $widget_id ) ? 
						$widget_id : 
						( isset( $_POST['cptd-search-widget-id'] ) ? 
							sanitize_text_field( $_POST['cptd_search']['widget-id'] ) :
							''
						); ?>" 
				>
				<input class="cptd-search-submit" type="submit" value="<?php echo $submit_text; ?>"/>
			</form>
		<?php
		echo $after_widget;
		wp_enqueue_style( 'cptd', cptd_url('/css/cptd.css') );

	} # end: widget()

} # end class: CPTD_Search_Widget
