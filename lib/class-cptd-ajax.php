<?php
/**
 * Registers calls with wp_ajax and wp_ajax_nopriv
 * Handles callbacks for wp_ajax and wp_ajax_nopriv
 * 
 */
class CPTD_Ajax{

	/**
	 * Class parameters
	 */

	/**
	 * @param 	array 	$actions 	The actions to register with wp_ajax
	 */
	static $actions = array('cptd_handle_from_title', 'cptd_slug_from_title');


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