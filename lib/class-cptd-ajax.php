<?php
/**
 * Registers calls with wp_ajax and wp_ajax_nopriv
 * Handles callbacks for wp_ajax and wp_ajax_nopriv
 * 
 * @since 	2.0.0
 */
class CPTD_Ajax{

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
		'cptd_handle_from_title', 
		'cptd_slug_from_title',
		'cptd_select_field_group'
	);


	/**
	 * Class methods
	 * 
	 * - Ajax callback methods
	 * - Helper methods
	 */


	/**
	 * Ajax callback methods
	 *
	 * - cptd_handle_from_title()
	 * - cptd_slug_from_title()
	 * - cptd_select_field_group()
	 */

	/**
	 *	Print a handle name suitable for post type registration, given a title via $_POST
	 * 
	 * @param 	string 	$_POST['title'] 	The title to convert into a handle
	 * @since 	2.0.0
	 */
	public static function cptd_handle_from_title(){
		$title = sanitize_text_field( $_POST['title'] );
		if( ! $title ) die();
		
		# if title ends in 's'
		if( 's' === strtolower( substr( $title, -1 ) ) ){
			$title = substr( $title, 0, -1 );
		}

		echo CPTD_Helper::clean_str_for_field( $title );
		die();
	} # end: cptd_handle_from_title()

	/**
	 * Print a slug suitable for URL usage, given a title via $_POST
	 *
	 * @param 	string 	$_POST['title']		The title to convert into a slug
	 * @since 	2.0.0
	 */
	public static function cptd_slug_from_title() {
		$title = sanitize_text_field( $_POST['title'] );
		if( ! $title ) die();
		
		echo CPTD_Helper::clean_str_for_url( $title );
		die();
	} # end: cptd_slug_from_title()

	/**
	 * Print a checkbox group of fields for the selected field group
	 *
	 * @param 	string 	$_POST['post_id'] 				The post ID of the post being edited
	 * @param 	string 	$_POST['field_group_post_id'] 	The post ID of the selected field group
	 * @param 	string 	$_POST['view_type'] 			(single|archive) The section for the selected field group
	 * @since 	2.0.0
	 */
	public static function cptd_select_field_group() {

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

		ob_start();
		?>
		<div class="cptd-field-select">
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
					$saved_fields = (array) get_post_meta( $post_id, '_cptd_meta_'. $_POST['view_type'] .'_fields', true );
				}

				# loop through the fields for this field group and generate checkboxes
				foreach( $r as $row ) {
					
					$value = unserialize( $row->meta_value );
					if( ! $value ) continue;
					?>
					<label>
						<input 
							type='checkbox' 
							name="_cptd_meta_<?php echo $_POST['view_type']; ?>_fields[]"
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
	
	} # end: cptd_select_field_group()
	

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
			add_action('wp_ajax_'.$action, array('CPTD_Ajax', $action));			
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
			), $args, 'cptd_action_button'
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
} # end class: CPTD_Ajax