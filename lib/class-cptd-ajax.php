<?php
class CPTD_Ajax{
	static $actions = array('cptd_post_type_handle_from_title', 'cptd_post_type_slug_from_title');

	# register actions with wp_ajax_
	public static function add_actions(){
		foreach(self::$actions as $action){
			add_action('wp_ajax_'.$action, array('CPTD_Ajax', $action));			
		}
	}
	/*
	* Ajax Actions
	*/
	public static function cptd_post_type_handle_from_title(){
		$title = sanitize_text_field($_POST['title']);
		if(!$title) die();
		
		# if title ends in 's'
		if('s' === strtolower(substr($title, -1))){
			$title = substr($title, 0, -1);
		}

		echo CPTD_Helper::clean_str_for_field($title);
		die();
	}

	public static function cptd_post_type_slug_from_title() {
		$title = sanitize_text_field($_POST['title']);
		if(!$title) die();
		
		echo CPTD_Helper::clean_str_for_url($title);
		die();
	}
	
	/*
	* Methods
	*/
	
	# display an action button section
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