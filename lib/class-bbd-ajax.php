<?php
/**
 * Registers calls with wp_ajax and wp_ajax_nopriv
 * Handles callbacks for wp_ajax and wp_ajax_nopriv
 * 
 * @since 	2.0.0
 */
class BBD_Ajax{

	/**
	 * Class parameters
	 */

	/**
	 * The actions to register with wp_ajax
	 *
	 * @param 	array 
	 * @since 	2.0.0
	 */
	static $actions = array(

		/* post type edit */
		'bbd_handle_from_title', 
		'bbd_slug_from_title',
		'bbd_save_slug',
		'bbd_select_field_group',
		'bbd_flush_post_type_cache',
		'bbd_save_caching_option',
	);


	/**
	 * Class methods
	 * 
	 * - Ajax callback methods
	 * 		- Post type edit
	 *		- Cache management
	 *
	 * - Helper methods
	 */

	/**
	 * Post type edit AJAX callbacks
	 *
	 * 		- bbd_handle_from_title()
	 * 		- bbd_slug_from_title()
	 * 		- bbd_save_slug()
	 * 		- bbd_select_field_group()
	 */

	/**
	 *	Print a handle name suitable for post type registration, given a title via $_POST
	 * 
	 * @param 	string 	$_POST['title'] 	The title to convert into a handle
	 * @since 	2.0.0
	 */
	public static function bbd_handle_from_title(){
		$title = sanitize_text_field( $_POST['title'] );
		if( ! $title ) die();
		
		# if title ends in 's'
		if( 's' === strtolower( substr( $title, -1 ) ) ){
			$title = substr( $title, 0, -1 );
		}

		echo BBD_Helper::clean_str_for_field( $title );
		die();
	} # end: bbd_handle_from_title()

	/**
	 * Print a slug suitable for URL usage, given a title via $_POST
	 *
	 * @param 	string 	$_POST['title']		The title to convert into a slug
	 * @since 	2.0.0
	 */
	public static function bbd_slug_from_title() {
		$title = sanitize_text_field( $_POST['title'] );
		if( ! $title ) die();
		
		echo BBD_Helper::clean_str_for_url( $title );
		die();
	} # end: bbd_slug_from_title()

	/**
	 * Callback for validating a post type or taxonomy slug when saving on the post edit screen
	 *
	 * @param 	string 	$_POST['slug'] 		The slug being saved
	 */
	public static function bbd_save_slug() {

		# Make sure the slug is non-empty.  If an empty slug is saved, we default to slug formed by page title
		if( empty( $_POST['slug'] ) ) {
			echo 1;
			die();
		}

		# get/sanitize the slug
		$slug = sanitize_text_field( $_POST['slug'] );

		# check if a or post or a page already has this slug
		if( $page = get_page_by_path( $slug, 'object', 'page' ) ) {
			
			echo '<p class="bbd-fail">There is already a page (' . $page->post_title . ') with this slug</p>';
			die();
		}

		# loop through post types and taxonomies and make sure the slug doesn't match
		foreach( array_merge( BBD::$post_type_ids, BBD::$taxonomy_ids ) as $id ) {

			$pt = new BBD_PT( $id );
			if( empty( $pt->slug ) ) continue;
			if( $slug == $pt->slug ) {
				echo '<p class="bbd-fail">There is another post type or taxonomy (' . $pt->plural . ') with this slug.</p>';
				die();
			}
		}

		# if we didn't encounter any reserved names, send back 1 for our JS 
		echo 1;

		die();

	} # end: bbd_save_slug()

	/**
	 * Print a checkbox group of fields for the selected field group
	 *
	 * @param 	string 	$_POST['post_id'] 				The post ID of the post being edited
	 * @param 	string 	$_POST['field_group_post_id'] 	The post ID of the selected field group
	 * @param 	string 	$_POST['view_type'] 			(single|archive) The section for the selected field group
	 * @since 	2.0.0
	 */
	public static function bbd_select_field_group() {

		# make sure we have a view type ('single' or 'archive')
		if( empty( $_POST['view_type'] ) ) die();
		$view_type = $_POST['view_type'];

		# make sure we have an ID for the field group being selected
		if( empty( $_POST['field_group_post_id'] ) || ! ( $field_group_post_id = intval( $_POST['field_group_post_id'] ) ) ) die();

		# get the current post ID if we have one
		$post_id = isset( $_POST['post_id'] ) ? $_POST['post_id'] : 0;

		global $wpdb;

		# get all the fields for this field group from the postmeta table
		$meta_query = "SELECT * FROM " . $wpdb->postmeta . 
			" WHERE post_id = " . $field_group_post_id . 
			" AND meta_key LIKE \"%field_%\"";
		$r = $wpdb->get_results( $meta_query );

		# sort the fields by the ACF order
		usort( $r, function( $a, $b ) {

			# unserialize values
			$value_a = unserialize( $a->meta_value );
			$value_b = unserialize( $b->meta_value );

			# compare `order_no`
			return strnatcmp( $value_a['order_no'], $value_b['order_no'] );

		});

		ob_start();
		?>
		<div class="bbd-field-select">
		<?php

			# if no fields were found
			if( ! $r ) {
				echo 'No fields were found for that field group.';
			}

			# if fields exist
			else{

				# get the saved fields, if any, so we can pre-check them
				$saved_fields = array();
				if( $post_id ) {
					$saved_fields = (array) get_post_meta( $post_id, '_bbd_meta_'. $_POST['view_type'] .'_fields', true );
				}

				# loop through the fields for this field group and generate checkboxes
				foreach( $r as $row ) {
					
					$value = unserialize( $row->meta_value );
					if( ! $value ) continue;
					?>
					<label>
						<input 
							type='checkbox' 
							name="_bbd_meta_<?php echo $_POST['view_type']; ?>_fields[]"
							value="<?php echo  $value['key']; ?>" 
							<?php checked( true, in_array( $value['key'], $saved_fields ) ); ?>
						/> <?php echo $value['label']; ?>
					</label>
				<?php
				} # end foreach: fields for this field group
			} # end else: fields exist
		?>
		</div>
		<?php

		# print the generated HTML
		$html = ob_get_contents();
		ob_end_clean();
		echo $html;
		die();
	
	} # end: bbd_select_field_group()

	/**
	 * Cache management AJAX callbacks
	 *
	 * 		- bbd_flush_post_type_cache
	 * 		- bbd_save_caching_option
	 */

	/**
	 * Flush the items stored in the Object Cache by the Big Boom Directory plugin
	 *
	 * @param 	$_POST['time'] 		Used along with the nonce
	 * @param 	$_POST['nonce'] 	A nonce generated by WP
	 *
	 * @since 	2.2.0
	 */
	public static function bbd_flush_post_type_cache() {

		$time = $_POST['time'];
		$nonce = $_POST['nonce'];
		$nonce_valid = wp_verify_nonce( $nonce, 'bbd-post-type-cache' . $time );

		if( ! $nonce_valid ) {
			echo bbd_fail( 'Sorry, something went wrong.' );
			die();
		}

		wp_cache_delete( 'bbd_post_types' );
		wp_cache_delete( 'bbd_post_types_meta' );
		echo bbd_success( 'Cache items deleted.' );
		die();

	} # end: bbd_flush_post_type_cache()

	/**
	 * Save the option to disable/enable the plugin's caching function
	 *
	 * @param 	$_POST['time'] 				Used along with the nonce
	 * @param 	$_POST['nonce'] 			A nonce generated by WP
	 * @param 	$_POST['disable_cache'] 	Whether to disable (1) or enable (0) the caching function
	 *
	 * @since 	2.2.0
	 */
	public static function bbd_save_caching_option() {

		$time = $_POST['time'];
		$nonce = $_POST['nonce'];
		$nonce_valid = wp_verify_nonce( $nonce, 'bbd-post-type-cache' . $time );

		if( ! $nonce_valid ) {
			echo bbd_fail( 'Sorry, something went wrong.' );
			die();
		}

		$disable_caching = ( '1' == $_POST['disable_cache'] ) ? true : false;

		$options = ! empty( BBD_Options::$options ) ? BBD_Options::$options : array();

		$options['disable_cache'] = $disable_caching;
		update_option( 'bbd_options', $options );

		echo bbd_success( 'Caching preference saved.' );

		die();

	} # end: bbd_save_caching_option()
	

	/**
	 * Helper methods
	 *
	 * - add_actions()
	 * - action_button()
	 */

	/**
	 * Register actions with wp_ajax_
	 * @since 	2.0.0
	 */
	public static function add_actions(){
		foreach(self::$actions as $action){
			add_action('wp_ajax_'.$action, array('BBD_Ajax', $action));			
		}
	}
	
	/**
	 * Display an action button section, with title, description, button, and container for resulting message
	 * 
	 * @param 	array 	$args {
	 *		Arguments for the action button to be displayed
	 * 
	 * 		@type 	string 	$id					The ID attribute for the button
	 * 		@type 	string 	$label				The text to use as the title for the section
	 * 		@type 	string 	$button_text		The text to display inside the button
	 * 		@type 	string 	$description		A description for what the action does
	 * 		@type 	string 	$instructions		Instructions for how to use the action
	 * }
	 * @since 	2.0.0
	 */
	public static function action_button($args){
		$args = shortcode_atts(
			array(
				'id' => '',
				'label' => '',
				'button_text' => 'Go',
				'class' => '',
				'description' => '',
				'instructions' => '',
			), $args, 'bbd_action_button'
		);
		extract($args);

		# make sure we have an ID
		if(!$id) return;
	?>
	<div class='action-button-container'>
		<?php 
		if($label){ 
			?><h3><?php echo $label; ?></h3><?php
		}
		if($description){
			?><p id='description'><?php echo $description; ?></p><?php
		}
		?>
		<button 
			id="<?php echo $id; ?>"
			class="button button-primary<?php if($class) echo ' '. $class; ?>"
		><?php echo $button_text; ?></button>
		<?php if($instructions){
			?><p class='description'><?php echo $instructions; ?></p><?php
		}
		?>
		<p class='message'></p>
	</div>
	<?php
	} # end: action_button()
} # end class: BBD_Ajax