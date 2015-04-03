<?php

class CPTD_search_widget extends WP_Widget{
var $acf_fields = array();
function __construct(){
	$widget_options = array(
		"classname" => "cptdir-search-widget",
		"description" => "Custom Post Type Directory search widget"
	);
	parent::__construct("cptdir_search_widget", "CPT Directory Search", $widget_options);
	$acf_fields = CPTD::get_acf_fields();
	$this->acf_fields = $acf_fields;

}
###
# Front end
###

# Widget content
function widget($args, $instance){
	extract($args, EXTR_SKIP);
	
	$title = ($instance['title']?$instance['title']:"");
	$view_all = ($instance['view_all']?$instance['view_all']:"");
	$view_all_link = ($instance['view_all_link']?$instance['view_all_link']:(get_option('cpt_search_page')?get_permalink(get_option('cpt_search_page')):""));
	$body = ($instance['body']?$instance['body']:"");
	$fields = ($instance['fields']?$instance['fields']:array());
	$submit_text = ($instance["submit_text"]?$instance["submit_text"]:"Search");
	?>
	
	<?php 
	echo $before_widget; 
		if($title) echo $before_title.$title.$after_title; 
		if($view_all && $view_all_link) echo "<p class='cptdir-view-all'><a href='" . $view_all_link . "'>" . $view_all . "</a></p>";
		if($body) echo"<p>". $body . "</p>";		
	?>
		<form method="post" id="cptdir-search-form" action="<?php if(get_option('cpt_search_page')) echo get_permalink(get_option('cpt_search_page')); ?>">
			<?php
			# Category taxonomy
			self::tax_dropdown("ctax");
			# Tag taxonomy
			self::tax_dropdown("ttax");
			# Custom fields
			$this->do_custom_fields_dropdown($fields);
			# Add hidden input to keep track of Widget ID
			?>
			<input type="hidden" 
				name="cptdir-search-widget-id" 
				value="<?php echo $widget_id ? $widget_id : ($_POST['cptdir-search-widget-id'] ? sanitize_text_field($_POST['cptdir-search-widget-id']):''); ?>" >
			<div style="clear: both;"></div>
			<input class="cptdir-search-submit" type="submit" value="<?php echo $submit_text; ?>"/>
		</form>
	<?php
	echo $after_widget;
}
# do taxonomy terms dropdown for widget, inputing either "ctax" or "ttax"
private static function tax_dropdown($s){
	# do nothing if input is not ctax or ttax
	if($s != "ctax" && $s != "ttax"){ return; }
	# get taxonomy object
	$tax = ($s == "ctax")?cptdir_get_cat_tax():cptdir_get_tag_tax();
	if($tax) do{
		# load existing terms into an array
		$aTerms = get_terms($tax->name);
		if(array() == $aTerms) break;
		# name for our select element
		$name = "cptdir-". $s . "-select";
		# produce dropdown
	?>
		<p class="cptdir-search-filter"><label for="<?php echo $name; ?>">
			<select name="<?php echo $name; ?>">
				<option value=""><?php echo $tax->labels["name"]; ?></option>
			<?php
			foreach($aTerms as $term){
			?>
				<option value="<?php echo $term->term_id; ?>" <?php if(array_key_exists($name, $_POST)) selected($term->term_id, $_POST[$name]); ?>><?php echo $term->name; ?></option>
			<?php
			}
			?>
			</select>
		</label></p>
	<?php
	} while(0); # endif: tax terms exist		
}
# custom fields dropdown for widget, inputting the selected widget field keys
private function do_custom_fields_dropdown($fields){
	if(!is_array($fields)) return;
	# Loop through acf fields (stored in $this object) and grab the ACF field arrays that are selected
	foreach($this->acf_fields as $field){
		if(in_array($field['name'], $fields)){
			# see if we have pre-defined choices and get array if we do
			if(array_key_exists("choices", $field)) $aValues = $field["choices"];
			# get values as strings if choices don't exist
			else $aValues = CPTD::get_meta_values($field['name']);
			if(array() != $aValues){
				$name = "cptdir-". $field['name'] . "-select";
			?>
				<p class="cptdir-search-filter"><label for="<?php echo $name; ?>">	
					<select name="<?php echo $name; ?>">
						<option value=""><?php echo $field['label']; ?></option>
						<?php
						foreach($aValues as $key => $value){
						?>
							<option value="<?php echo is_string($key) ? $key : $value; ?>"><?php echo $value; ?></option>
						<?php
						}
						?>
					</select>
				</label></p>
			<?php
			}
		}
	}
}

###
# Backend UI
###

function form($instance){
	?>
	<p><label for="<?php echo $this->get_field_id('title'); ?>">
	Title: 
	<input type="text" class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr($instance['title']); ?>"/>
	</label></p>
	<p><label for="<?php echo $this->get_field_id('view_all_link'); ?>">
	"View All" link:
	<input type="text" class="widefat" id="<?php echo $this->get_field_id('view_all_link'); ?>" name="<?php echo $this->get_field_name('view_all_link'); ?>" value="<?php echo esc_attr($instance['view_all_link']); ?>"/>
	</label></p>	
	<p><label for="<?php echo $this->get_field_id('view_all'); ?>">
	Text for "View All" link:
	<input type="text" class="widefat" id="<?php echo $this->get_field_id('view_all'); ?>" name="<?php echo $this->get_field_name('view_all'); ?>" value="<?php echo esc_attr($instance['view_all']); ?>"/>
	</label></p>	
	<p><label for="<?php echo $this->get_field_id('body'); ?>"/>
	Description: 
	<textarea class="widefat" id="<?php echo $this->get_field_id('body'); ?>" name="<?php echo $this->get_field_name('body'); ?>"><?php echo esc_attr($instance['body']); ?></textarea>
	</label></p>
	Custom Fields:
	<?php
	$aFields = $this->acf_fields;
	foreach($aFields as $aField){
	?>
		<p><label for="<?php echo $this->get_field_id('fields[' . $aField['name'] . ']'); ?>">
			<input type="checkbox" name="<?php echo $this->get_field_name('fields'); ?>[]" id="<?php echo $this->get_field_id('fields[' . $aField['name'] . ']'); ?>" value="<?php echo $aField['name']; ?>" 
				<?php
					if(is_array($instance['fields'])) foreach ($instance['fields'] as $f) { checked($f, $aField['name']);  }
				?>
			/><?php echo $aField['label']; ?>
		</label></p>
	<?php
	}
	?>
	<p><label for="<?php echo $this->get_field_id('submit_text'); ?>">
	Text for Submit button:
	<input type="text" class="widefat" id="<?php echo $this->get_field_id('submit_text'); ?>" name="<?php echo $this->get_field_name('submit_text'); ?>" value="<?php echo esc_attr($instance['submit_text']); ?>"/>
	</label></p>	
	<?php
}
} # end: class
add_action("widgets_init", "cptdir_init");
function cptdir_init(){ register_widget("CPTD_search_widget"); }
