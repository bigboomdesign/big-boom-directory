<?php
/**
 * The BBD Search Widget class
 *
 * Handles the backend widget settings form
 * Handles the front end display of the widget and widget search results
 * Handles the shortcode implementation for `bbd-search`
 *
 * @since 	2.0.0
 */
class BBD_Search_Widget extends WP_Widget {

	/**
	 * An instance array as passed by $this->form()
	 * Helps to refer to $this->instance within other functions 
	 *
	 * @param 	array
	 * @since 	2.0.0
	 */
	var $instance = array();

	/**
	 * The instance field keys for individual widgets
	 *
	 * @param 	array
	 * @since 	2.0.0
	 */
	var $instance_keys = array(
		
		// The widget title
		'title',

		// The widget description
		'description', 

		// the URL to link to for 'View All'
		'view_all_link',

		// customizable 'View All' link text
		'view_all',

		// customize text for submit button
		'submit_text',

		// array of post IDs for BBD post types to be searched through
		'post_types',

		// array of post IDs for BBD taxonomies to use as search filters
		'taxonomies',

		// array of field keys to use as search filters
		'meta_keys',

		// page ID to post results to
		'search_page',

		// show the search widget on the search results page
		'show_widget_on_search_results_page',

		// excerpt length for the search results
		'excerpt_length',

		// array of field keys to show on the search results page
		'search_results_fields',
	
	); # end: $instance_keys

	/**
	 * The existing meta keys for all BBD posts, in alphabetical order
	 * These are the available search filters for the widget
	 *
	 * @param 	array
	 * @since 	2.0.0
	 */
	var $field_keys = array();

	/**
	 * Whether we are in the middle of a search result loop 
	 * Helps with using the_content() filter inside the search results loop
	 */
	var $doing_search_results = false;

	/**
	 * Class methods
	 *
	 * - __construct()
	 *
	 * - form()
	 * 		- field_type_details()
	 *
	 * - widget()
	 *
	 * - get_instance()
	 * - get_search_results_html()
	 * - get_shortcode_html()
	 */

	/**
	 * Construct a new instance
	 * 
	 * @since 	2.0.0
	 */
	public function __construct(){

		$widget_options = array(
			"classname" => "bbd-search-widget",
			"description" => "Advanced search widget for Big Boom Directory listings"
		);

		parent::__construct("bbd_search_widget", "Big Boom Directory Search", $widget_options);

		# if we are viewing widget search results, add filter for the_content
		# note we don't have a widget_id at this point, so we need to do a test in the callback function
		# to match the posted widget_id
		if( isset( $_POST['bbd_search'] ) ) {
			add_filter('the_content', array( $this, 'get_search_results_html' ) );
		}

		# add a shortcode that invokes this instances callback function
		add_shortcode( 'bbd-search', array( $this, 'get_shortcode_html' ) );

	} # end: __construct()

	/**
	 * Generate HTML for the backend widget settings form
	 *
	 * @param 	array 	$instance 	The current widget settings for this instance
	 * @since 	2.0.0
	 */
	public function form( $instance ) {
	?>
	<div id='bbd-search-form' class='bbd-widget-form'>
		<?php
		# show the shortcode for this widget
		if( ! empty( $this->number ) && -1 != $this->number && '__i__' != $this->number ) {
			echo '<p><b>Shortcode:</b><br /><code>[bbd-search widget_id="' . $this->number . '"]</code></p>';
		}

		# the widget title 
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>">
		Title: 
		<input type="text" class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php if( ! empty( $instance['title'] ) ) echo esc_attr( $instance['title'] ); ?>"/>
		</label></p>

		<?php 

		# The View All link 
		?>
		<p><label for="<?php echo $this->get_field_id('view_all_link'); ?>">
		"View All" link:
		<input type="text" class="widefat" id="<?php echo $this->get_field_id('view_all_link'); ?>" name="<?php echo $this->get_field_name('view_all_link'); ?>" value="<?php if( ! empty( $instance['view_all_link'] ) ) echo esc_attr($instance['view_all_link']); ?>"/>
		</label></p>	

		<?php 

		# the View All link text 
		?>
		<p><label for="<?php echo $this->get_field_id('view_all'); ?>">
		Text for "View All" link:
		<input type="text" class="widefat" id="<?php echo $this->get_field_id('view_all'); ?>" name="<?php echo $this->get_field_name('view_all'); ?>" value="<?php if( ! empty( $instance['view_all'] ) ) echo esc_attr($instance['view_all']); ?>"/>
		</label></p>	

		<?php 

		# The description field 
		?>
		<p><label for="<?php echo $this->get_field_id('description'); ?>"/>
		Description: 
		<textarea class="widefat" id="<?php echo $this->get_field_id('description'); ?>" name="<?php echo $this->get_field_name('description'); ?>"><?php if( ! empty( $instance['description'] ) ) echo esc_attr($instance['description']); ?></textarea>
		</label></p>
		
		<?php 

		# The `submit_text` option 
		?>
		<p><label for="<?php echo $this->get_field_id('submit_text'); ?>">
			Text for Submit button:
			<input type="text" class="widefat" id="<?php echo $this->get_field_id('submit_text'); ?>" name="<?php echo $this->get_field_name('submit_text'); ?>" value="<?php if( ! empty( $instance['submit_text'] ) ) echo esc_attr( $instance['submit_text'] ); ?>"/>
		</label></p>

		<?php 

		# The search results page dropdown 
		?>
		<p><label for='<?php echo $this->get_field_id('search_page'); ?>'>
		Search Results Page:<br />
		<?php
			$search_page = ! empty( $instance['search_page'] )  ? 
				$instance['search_page'] :
				(
					! empty( BBD_Options::$options['search_page'] ) ?
						BBD_Options::$options['search_page'] : 
						''
				);

			wp_dropdown_pages( array( 
				'name' => $this->get_field_name('search_page'),
				'selected' => $search_page 
			));
		?>
		</label></p>
		<?php 

		# Whether to show the search widget on the results page 
		?>
		<p><label for='<?php echo $this->get_field_id('show_widget_on_search_results_page'); ?>'>
			<?php
				$show_widget_on_search_results_page = ( '__i__' == $this->number ) ? true : (
					! empty( $instance['show_widget_on_search_results_page'] ) ? true : false
				);
			?>
			<input 
				type='checkbox' 
				name='<?php echo $this->get_field_name( 'show_widget_on_search_results_page' ); ?>' 
				id='<?php echo $this->get_field_id( 'show_widget_on_search_results_page' ); ?>' 
				<?php checked( true, $show_widget_on_search_results_page ); ?>
			/> Show Widget On Search Results Page
		</label></p>
		<?php

		# Excerpt length
		?>
		<p><label>Excerpt length for results:
			<input type='text' class='widefat' 
				id='<?php echo $this->get_field_id('excerpt_length'); ?>' 
				name="<?php echo $this->get_field_name('excerpt_length'); ?>" 
				value="<?php echo ! empty( $instance['excerpt_length'] ) ?  
					esc_attr( $instance['excerpt_length'] ) :
					250; ?>"
			/>
		</label></p>
		<?php

		# Checkboxes for post types
		$post_type_args = array(
			'selected' => ( ! empty( $instance['post_types'] ) ? $instance['post_types'] : array() ),
			'label_class' => 'post-type-select',
			'field_id' => $this->get_field_id( 'search_widget_post_type' ),
			'field_name' => $this->get_field_name( 'post_types' ),
		);
		?>
		<h4>Post Types</h4>
		<p>Choose which post types should be searched by the widget</p>
		<?php
		echo BBD_Helper::checkboxes_for_post_types( $post_type_args );

		# Checkboxes to select taxonomies
		$tax_args = array(
			'selected' => ( ! empty( $instance['taxonomies'] ) ? $instance['taxonomies'] : array() ),
			'label_class' => 'taxonomy-select',
			'field_id' => $this->get_field_id( 'search_widget_taxonomy' ),
			'field_name' => $this->get_field_name( 'taxonomies' ),
		);
		?>
		<h4>Taxonomy Filters</h4>
		<p>Select the taxonomies to be offered to the user as search filters</p>
		<?php
		echo BBD_Helper::checkboxes_for_taxonomies( $tax_args );

		# Check if we have field keys to use for additional options
		$this->field_keys = BBD_Helper::get_all_field_keys();
		if( ! empty( $this->field_keys ) ) {

			/**
			 * Checkboxes to select fields to use for search filtering
			 */

			# arguments for the field set
			$search_filter_meta_keys_args = array(
				'selected' 	=> ! empty( $instance['meta_keys'] ) ? $instance['meta_keys'] : array(),
				'label_class' 	=> 'meta-keys-select',
				'field_id' 		=> $this->get_field_id( 'meta_keys' ),
				'field_name' 	=> $this->get_field_name( 'meta_keys' ),
				'field_class' 	=> 'bbd-search-widget-field',
			);

			# set the instance parameters for use in helper functions
			$this->instance = $instance;

			# hook into the action after each field checkbox to add filter details
			add_action( 'bbd_after_field_checkbox', array( $this, 'field_type_details' ), 10, 1 );

			# do checkboxes for field filters
			?>
			<h4>Custom Field Filters</h4>
			<p>Select the fields to be offered to the user as search filters</p>
			<div class='bbd-search-field-filter-select'>
				<?php BBD_Helper::checkboxes_for_fields( $search_filter_meta_keys_args ); ?>
			</div>
			<?php

			# remove the action that fires after each field
			remove_action( 'bbd_after_field_checkbox', array( $this, 'field_type_details' ) );

			/**
			 * Draggable fields to show on search results page
			 */

			# the saved value for this widget
			$search_results_fields = ! empty( $instance['search_results_fields'] ) ? $instance['search_results_fields'] : array();

			$search_results_fields_args = array(
				'selected'	=> ! empty( $instance['search_results_fields'] ) ? $instance['search_results_fields'] : array(),
				'heading' 	=> '<h4>Custom Fields for Search Results</h4>',
				'description'	=> '<p>Select the fields you\'d like to display for each post on the search results page</p>',
				'label_class' 	=> 'search-results-fields-select',
				'field_id'		=> $this->get_field_id( 'search_results_fields' ),
				'field_name'	=> $this->get_field_name( 'search_results_fields' ),
				'field_class'	=> 'bbd-search-widget-field'
			);

			?>
			<h4>Custom Fields for Search Results</h4>
			<p>Select the fields you'd like to display for each post on the search results page</p>
			<?php 

			# draggable fields area
			BBD_Helper::draggable_fields( $search_results_fields_args );
			
		} # end if: field keys exist

		# initilize the main widget JS routines after save
	?>
		<script type='text/javascript'>initSearchWidget( jQuery )</script>
	<?php
		do_action( 'bbd_after_search_widget_form' );
	?>
	</div><?php # .bbd-widget-form ?>
	<?php

	} # end: form()


	/**
	 * Add field detail settings for each field type
	 *
	 * @param 	BBD_Field		The field whose checkbox is active in the loop
	 * @since 	2.0.0
	 */
	public function field_type_details( $field ) {

		# Radio buttons for the different field types
		?>
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
					<?php if( ! empty( $this->instance[ $field_type_key ] ) ) echo checked( $this->instance[ $field_type_key ], 'text' ); ?>
				/> Text
			</label>

			<?php # The `select` option ?>
			<label for='<?php echo $this->get_field_id( $field_type_key . '_select' ); ?>' >
				<input id= '<?php echo $this->get_field_id( $field_type_key . '_select' ); ?>' 
					type='radio' 
					value='select' 
					name='<?php echo $this->get_field_name(  $field_type_key ); ?>' 
					<?php if( ! empty( $this->instance[ $field_type_key ] ) ) echo checked( $this->instance[ $field_type_key ], 'select' ); ?>
				/> Select
			</label>

			<?php # The `checkbox` option ?>
			<label for='<?php echo $this->get_field_id( $field_type_key . '_checkbox' ); ?>' >
				<input id= '<?php echo $this->get_field_id( $field_type_key . '_checkbox' ); ?>' 
					type='radio' 
					value='checkbox' 
					name='<?php echo $this->get_field_name(  $field_type_key ); ?>' 
					<?php if( ! empty( $this->instance[ $field_type_key ] ) ) echo checked( $this->instance[ $field_type_key ], 'checkbox' ); ?>
				/> Checkboxes
			</label>
			<?php

			# do an action to allow addition of more field types
			do_action( 'bbd_field_type_details', $this, $field );
		?>
		</div>
		<?php
	} # end: field_type_details()

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
	
		$search_page = ! empty( $instance['search_page'] ) ?
			$instance['search_page'] :
			(
				! empty( BBD_Options::$options['search_page'] ) ?
					BBD_Options::$options['search_page'] :
					''
			);

		# the widget title
		$title = ( ! empty( $instance['title'] ) ? $instance['title'] : '' );

		# The 'View All' text
		$view_all = ( ! empty( $instance['view_all'] ) ? $instance['view_all'] : '' );

		# The 'View All' URL
		$view_all_link = ( ! empty( $instance['view_all_link'] ) ? 
			$instance['view_all_link'] : 
			( ! empty( BBD_Options::$options['search_page'] ) ? 
				get_permalink( BBD_Options::$options['search_page'] ) : 
				""
			)
		);

		# The content to print above the search widget
		$description = ( ! empty( $instance['description'] ) ? $instance['description'] : '' );

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

		if( $view_all && $view_all_link ) echo "<p class='bbd-view-all'><a href='" . $view_all_link . "'>" . $view_all . "</a></p>";
		if( $description ) echo"<p>". $description . "</p>";		
		?>
			<div class='bbd-search-widget-container'>
			<form method="post" 
				id="bbd-search-form" 
				action="<?php if( ! empty( $search_page ) ) echo get_permalink( $search_page ); ?>"
			><?php

				# Taxonomy filters 
				foreach( $taxonomies as $tax_id ) {

					$tax = new BBD_Tax( $tax_id );

					$setting = array(
						'id' => 'taxonomy_' . $tax_id,
						'label' => $tax->singular,
						'type' => 'select',
					);
					?>
					<div class='bbd-search-filter'>
						<?php $tax->get_form_element_html( $setting, 'bbd_search', $_POST ); ?>
					</div>
					<?php

				} # end foreach: $taxonomies

				# Loop through selected filters
				if( ! empty( $meta_keys ) ) 
				foreach( $meta_keys as $meta_key ) {

					$field = new BBD_Field( $meta_key );

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
					<div class='bbd-search-filter'>
						<?php $field->get_form_element_html( $setting ); ?>
					</div>
					<?php

				} # end foreach: $this->fields

				# Add hidden input to keep track of Widget ID
				?>
				<input type="hidden" 
					name="bbd_search[widget_id]" 
					value="<?php echo isset( $widget_id ) ? 
						$widget_id : 
						( isset( $_POST['bbd_search']['widget_id'] ) ? 
							sanitize_text_field( $_POST['bbd_search']['widget_id'] ) :
							''
						); ?>" 
				/>
				<input type="hidden" 
					name="bbd_search[widget_number]" 
					value="<?php echo isset( $this->number ) ? 
						$this->number : 
						( isset( $_POST['bbd_search']['widget_number'] ) ? 
							sanitize_text_field( $_POST['bbd_search']['widget_number'] ) :
							''
						); ?>" 
				/>
				<input class="bbd-search-submit" type="submit" value="<?php echo $submit_text; ?>" />
			</form>
			</div><!-- .bbd-search-widget-container -->
		<?php
		echo $after_widget;
		wp_enqueue_style( 'bbd', bbd_url('/css/bbd.css'), null, true );

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

		if( ! intval( $widget_number ) ) return array();
		
		# get the settings for this widget (includes all instances)
		$widget_settings_all = $this->get_settings();

		if( empty( $widget_settings_all[ $widget_number ] ) ) return array();

		# set the object property for later use
		$this->instance = $widget_settings_all[ $widget_number ];

		return $widget_settings_all[ $widget_number ];

	} # end: get_instance()

	/**
	 * Generate the HTML for the widget search results
	 *
	 * @param 	array 	$_POST['bbd_search'] 	The user-submitted search parameters
	 * @return 	string
	 * @since 	2.0.0
	 */
	public function get_search_results_html( $content ) {

		global $bbd_view;

		# make sure we don't recurse when doing search results excerpts
		if( $this->doing_search_results ) return $content;

		# get the settings for this widget instance (or the posted instance if different)
		$widget_number = isset( $_POST['bbd_search']['widget_number'] ) ?
			$_POST['bbd_search']['widget_number'] : 
			$this->number;

		$instance = $this->get_instance( $widget_number );

		if( ! $instance ) return $content;

		# get the meta keys to be displayed for each post
		if( ! empty( $instance['search_results_fields'] ) ) {

			# set up the view for this set of search results
			$bbd_view->field_keys = $instance['search_results_fields'];
			foreach( $bbd_view->field_keys as $field_key ) {

				$field = new BBD_Field( $field_key );
				
				# try and load ACF info 
				$field->get_acf_by_key();

				# load social fields into the view object
				if( $bbd_view->auto_detect_social ) {
					if( $field->is_social_field ) {
						$bbd_view->social_fields_to_check[] = $field->key;
					}
				}

				# add the field object to the view object
				$bbd_view->fields[] = $field;

			} # end foreach: field keys

		} # end if: search results fields are set

		# excerpt length
		$excerpt_length = ! empty( $instance['excerpt_length'] ) ? $instance['excerpt_length'] : 250;

		# get the post type names from the widget settings
		if( empty( $instance['post_types'] ) ) $post_type_ids = BBD::$post_type_ids;
		else $post_type_ids = $instance[ 'post_types' ];

		$post_type_names = array();

		foreach( $post_type_ids as $pt_id ) {

			$pt = new BBD_PT( $pt_id );
			$post_type_names[] = $pt->handle;
		}

		# get the meta keys from the widget settings
		if( ! empty( $instance['meta_keys'] ) ) $meta_keys = $instance['meta_keys'];
		else $meta_keys = array();


		# get the sanitized search form input
		$raw_input = $_POST['bbd_search'];
		$form_input = array();

		foreach( $raw_input as $k => $v ) {

			if( empty( $v ) ) continue;
			$form_input[ sanitize_key( $k ) ] = sanitize_text_field( $v );
		}

		# query pieces
		$query_args = array( 
			'post_type' => $post_type_names,
			'posts_per_page' => -1,
			'relation' => 'OR',
			'orderby' => 'title',
			'order' => 'ASC',
		);

		$tax_query = array();
		$meta_query = array();

		# loop through sanitized form inputs
		foreach( $form_input as $k => $v ) {

			# load taxonomies into $tax_query
			if( false !== strpos( $k, 'taxonomy_' ) ) {
				
				# get the post ID for this taxonomy (post type is `bbd_tax`)
				$tax_id = str_replace( 'taxonomy_', '', $k );

				# get the taxonomy object
				$tax = new BBD_Tax( $tax_id );
				if( empty( $tax->handle ) ) continue;

				$tax_query[] = array(
					'taxonomy' => $tax->handle,
					'field' => 'term_id',
					'terms' => intval( $v ),
				);

				continue;
			}

			# load custom fields into $meta_query
			else {

				# check if the key is in the selected meta keys for the widget
				if( in_array( $k, $meta_keys ) ) {

					# get the field type
					if( empty( $instance[ $k . '_field_type' ] ) ) continue;
					$field_type = $instance[ $k . '_field_type' ];

					$meta_query[] = array(
						'key' => $k,
						'value' => $v,
						'compare' => (
							'text' == $field_type ?
								'LIKE' :
								'='
						)
					);

					continue;

				} # end if: form input is in the widget's meta keys

				# if the field is not in the array, it could be a checkbox where the field name contains
				# both the key and the value (e.g. `available_yes` )
				foreach( $meta_keys as $test_key ) {

					# the key which will trigger a positive match for checkboxes
					$match_key = $test_key . '_' . BBD_Helper::clean_str_for_field( $v );

					if( $match_key == $k ) {
						$meta_query[] = array(
							'key' => $test_key,
							'value' => $v,
							'compare' => '=',
						);
					}

				} # end foreach: widget meta keys
			
			} # end else: custom field instead of taxonomy

		} # end foreach: form inputs


		if( ! empty( $tax_query ) ) {
			$tax_query['relation'] = 'OR';
			$query_args['tax_query'] = $tax_query;
		}
		if( ! empty( $meta_query ) ) {
			$meta_query['relation'] = 'OR';
			$query_args['meta_query'] = $meta_query;
		}

		# apply a filter to the query args
		$query_args = apply_filters( 'bbd_search_widget_query_args', $query_args, $this );

		$search_query = new WP_Query( $query_args );

		$search_query = apply_filters( 'bbd_search_widget_query', $search_query, $this );

		ob_start();

		?>
		<div id='bbd-search-results' class='<?php echo $this->id; ?>'>
		<?php
			/**
			 * Display the search widget if needed
			 */
			if( ! empty( $instance['show_widget_on_search_results_page'] ) ) {
				echo do_shortcode( '[bbd-search widget_id="' . $widget_number . '"]' );
			}

			# if posts were found
			if( $search_query->have_posts() ) {

				$this->doing_search_results = true;

				while( $search_query->have_posts() ) {

					$search_query->the_post();
					$post = $search_query->post;

					# post title header
					?>
					<div class='search-results-item'>
					<h2><a href='<?php echo get_the_permalink( $post->ID ); ?>'><?php echo $post->post_title; ?></a></h2>
					<?php
					
					# execute an action that the user can hook into
					do_action( 'bbd_before_search_result', $post->ID, $this );

					# show any fields for this post if applicable
					if( ! empty( $bbd_view->fields ) ) {
					?>
						<div class='search-results-fields'>
						<?php
														
							foreach( $bbd_view->fields as $field ) {
								do_action( 'bbd_pre_render_field_' . $field->key, $field );
								bbd_field( $post->ID, $field );
								do_action( 'bbd_post_render_field_' . $field->key, $field );
							}
						?>
						</div>
					<?php
					} # end: fields exist for this widget's search results view

					# get the post excerpt
					$excerpt = '';

					if( ! empty( $post->post_excerpt ) ) {
						$excerpt = apply_filters( 'the_excerpt', $post->post_excerpt );
					}
					else{
						$excerpt = apply_filters( 'the_content', $post->post_content );
						$excerpt = BBD_Helper::make_excerpt( $excerpt, $excerpt_length );
					}
					?>
					<div class='search-results-excerpt'><?php echo $excerpt; ?></div>
					<?php
					do_action( 'bbd_after_search_result', $post->ID, $this );
					?>
					</div><!-- .bbd-search-result-item -->
					<?php
				}

				wp_reset_query();
				$this->doing_search_results = false;

			} # end if: posts were found

			# if no posts were found
			else {
			?>
				<p>Sorry, we didn't find any results.</p>
			<?php
			}
		?>
		</div>
		<?php
		$html = ob_get_contents();
		ob_end_clean();

		wp_enqueue_style( 'bbd', bbd_url('/css/bbd.css'), null, true );
		return $html;
	} # end: get_search_results_html()

	/**
	 * Handler for the `bbd-search` shortcode
	 *
	 * @param 	array 	$atts 	The shortcode attributes submitted by the user
	 * @return 	string
	 * @since 	2.0.0
	 */
	public function get_shortcode_html( $atts ) {

		$atts = shortcode_atts( array(
			'widget_id' => '',
		), $atts, 'bbd-search');

		$instance = $this->get_instance( $atts['widget_id'] );

		$instance['widget_id'] = $atts['widget_id'];
		
		if( empty( $instance ) ) return '';

		ob_start();
		
		the_widget( get_class( $this ), $instance );

		$html = ob_get_contents();
		ob_end_clean();

		return $html;

	} # end: get_shortcode_html()

} # end class: BBD_Search_Widget