<?php
class CPTD_Options{
	# Static variables are set after class definition
	## available settings
	static $settings;
	## saved options
	static $options = array();
	
	static $sections;
	static $default_section = 'cptd_main';
		
	# Display field input
	public static function do_settings_field($setting, $option = 'cptd_options'){
		# the option `cptd_options` can be replaced on the fly and will be passed to handler functions
		$setting['option'] = $option;
		
		# fill out missing attributes for this option and its choices
		$setting = CPTD_Helper::get_field_array($setting);

		# the arrayed name of this setting, such as `cptd_options[my_setting]`
		$setting['option_name'] = (
			$option ? $option.'['.$setting['name'].']' : $setting['name']
		);
				
		# call one of several handler functions based on what type of field we have
		
		## see if a self method is defined having the same name as the setting type
		if(isset($setting['type']) && method_exists(get_class(), $setting['type'])) self::$setting['type']($setting);
		else if(!isset($setting['type'])) $setting['type'] = 'text';
		
		## special cases
		switch($setting['type']){
			case "textarea":
				self::textarea_field($setting);
			break;
			case 'checkbox':
				self::checkbox_field($setting);
			break;
			case 'select':
				self::select_field($setting);
			break;
			case 'radio':
				self::radio_field($setting);
			break;			
			case "single-image":
				self::image_field($setting);
			break;
			default: self::text_field($setting);
		} # end switch: setting type
		
		if(array_key_exists('description', $setting)) {
		?>
			<p class='description'><?php echo $setting['description']; ?></p>
		<?php
		}
		# Child fields (for conditional logic)
		if(array_key_exists('choices', $setting)){
			# keep track of which fields we've displayed (in case two choices have the same child)
			$aKids = array();

			# Loop through choices and display and children
			foreach($setting['choices'] as $choice){
				if(array_key_exists('children', $choice)){
					foreach($choice['children'] as $child_setting){
						# add this child to the array of completed child settings
						if(!in_array($child_setting['name'], $aKids)){
							$aKids[] = $child_setting['name'];
							# note the child field div is hidden unless the parent option is selected
						?><div 
							id="child_field_<?php echo $child_setting['name']; ?>"
							style="display: <?php echo isset(self::$options[$setting['name']]) ? (self::$options[$setting['name']] == $choice['value'] ? 'block' : 'none') : '';?>"
						>
							<h4><?php echo $child_setting['label']; ?></h4>
							<?php self::do_settings_field($child_setting); ?>
						</div>
						<?php
						}
					}
				} # end: choice has children
			} # end: foreach: choices
		} # end: setting has choices
	} # end: do_settings_field()
	
	## Text field
	public static function text_field($setting){
		extract($setting);
		$val = self::get_option_value($setting);
		?><input 
			id="<?php echo $name; ?>" name="<?php echo $setting['option_name']; ?>" 
			class="regular-text<?php if(isset($class)) echo ' ' . $class; ?>" type='text' value="<?php echo $val; ?>"
			<?php echo self::data_atts($setting); ?>
		/>

		<?php	
	} # end: text_field()
	
	## Textarea field
	public static function textarea_field($setting){
		extract($setting);
		$val = self::get_option_value($setting);	
		?><textarea 
			id="<?php echo $name; ?>" name="<?php echo $setting['option_name']; ?>" 
			class="<?php if(isset($class)) echo $class; ?>"			
			cols='40' rows='7'
			<?php echo self::data_atts($setting); ?>
		><?php echo $val; ?></textarea>
		<?php
	} # end: textarea_field()
	
	## Checkbox field
	public static function checkbox_field($setting){
		extract($setting);
		foreach($choices as $choice){
		?><label 
			class="checkbox <?php if(isset($label_class)) echo $label_class; ?>"
			for="<?php echo $choice['id']; ?>"
		>
			<input 
				type='checkbox'
				id="<?php echo $choice['id']; ?>"
				name="<?php echo self::get_choice_name($setting, $choice); ?>"
				value="<?php echo $choice['value']; ?>"
				class="<?php if(isset($class)) echo $class; if(array_key_exists('class', $choice)) echo ' ' . $choice['class']; ?>"
				<?php echo self::data_atts($choice); ?>
				<?php checked(true, '' != self::get_option_value($setting, $choice)); ?>						
			/>&nbsp;<?php echo $choice['label']; ?> &nbsp; &nbsp;
		</label>
		<?php
		}
	} # end: checkbox_field()
	
	## Radio Button field
	public static function radio_field($setting){
		extract($setting);
		$val = self::get_option_value($setting);
		foreach($choices as $choice){
				$label = $choice['label']; 
				$value = $choice['value'];
			?><label 
				class="radio <?php if(isset($label_class)) echo $label_class; ?>"
				for="<?php echo $choice['id']; ?>"
			>
				<input type="radio" id="<?php echo $choice['id']; ?>" 
				name="<?php echo $setting['option_name']; ?>" 
				value="<?php echo $value; ?>"
				class="<?php if(isset($class)) echo $class; if(array_key_exists('class', $choice)) echo ' ' . $choice['class']; ?>"
				<?php echo self::data_atts($choice); ?>				
				<?php checked($value, $val); ?>
			/>&nbsp;<?php echo $label; ?></label>&nbsp;&nbsp;
			<?php
		}
	} # end: radio_field()
	
	## <select> dropdown field
	public static function select_field($setting){		
		extract($setting);
		$val = self::get_option_value($setting);
	?><select 
		id="<?php echo $name; ?>"
		name="<?php echo $setting['option_name']; ?>"
		<?php echo self::data_atts($setting); ?>		
		<?php if(isset($class)) echo "class='".$class."'"; ?>
	>
		<?php 
		foreach($choices as $choice){
			# if $choice is a string
			if(is_string($choice)){
				$label = $choice;
				$value = CPTD_Helper::clean_str_for_field($choice);
			}
			# if $choice is an array
			elseif(is_array($choice)){
				$label = $choice['label'];
				$value = isset($choice['value']) ? $choice['value'] : CPTD_Helper::clean_str_for_field($choice['label']);
			}
		?>
			<option 
				value="<?php echo $value; ?>"
				<?php if(array_key_exists('class', $choice)) echo "class='".$choice['class']."' "; ?>
				<?php echo self::data_atts($choice); ?>					
				<?php selected($val, $value ); ?>					
			><?php echo $label; ?></option>
		<?php
		} # end foreach: $choices
		?>
		
	</select><?php
	} # end: select_field()
	
	## Image field
	public static function image_field($setting){
		# this will set $name for the field
		extract($setting);
		$val = self::get_option_value($setting);
		# current value for the field
		?><input 
			type='text'
			id="<?php echo $name; ?>" 
			class="regular-text text-upload <?php if($class) echo $class; ?>"
			name="<?php echo $setting['option_name']; ?>"
			value="<?php if($val) echo esc_url( $val ); ?>"
		/>		
		<input 
			id="media-button-<?php echo $name; ?>" type='button'
			value='Choose/Upload image'
			class=	'button button-primary open-media-button single'
		/>
		<div id="<?php echo $name; ?>-thumb-preview" class="cptd-thumb-preview">
			<?php if($val){ ?><img src="<?php echo $val; ?>" /><?php } ?>
		</div>
		<?php
	} # end: image_field()

	# matching a function name with the respecting $setting['type'] (e.g. `on_the_fly` or `my_custom_option_type`) 
	# allows for creation of "on the fly" option types	
	# note that with "on the fly" types, no special case needs to be added in the main switch 
	# statement in self::do_settings_field()
	public static function on_the_fly($setting){
		# e.g.
		$setting['choices'] = array(
			'Sound of buzzards breaking',
			'Sound of a breeding holstein',
			'Laser beams'
		);
		$setting['type'] = 'radio';
		self::do_settings_field($setting);
	}
	
	## Return a string of data attributes for fields or choices
	public static function data_atts($setting){
		if(!array_key_exists('data', $setting)) return;
		$out = '';
		foreach($setting['data'] as $k => $v){
			$out .= "data-{$k}='{$v}' ";
		}
		return $out;
	} # end: data_atts()
	
	# Register settings
	public static function register_settings(){
		# main option for this plugin
		register_setting( 'cptd_options', 'cptd_options', array('CPTD_Options', 'validate_options') );
		# add sections
		foreach(self::$sections as $section){
			add_settings_section(
				$section['name'], $section['title'], array('CPTD_Options', 'section_description'), 'cptd_settings'
			);
		}
		# add fields
		foreach(self::$settings as $setting){
			add_settings_field($setting['name'], $setting['label'], array('CPTD_Options','do_settings_field'), 'cptd_settings', ( array_key_exists('section', $setting) ? $setting['section'] : self::$default_section), $setting);
		}	
	} # end: register_settings()
	
	# Section description
	public static function section_description($section){
		# get ID of section being displayed
		$id = $section['id'];
		# loop through sections and display the correct description
		foreach(self::$sections as $section){
			if($section['name'] == $id && array_key_exists('description', $section)){
				echo $section['description'];
				break;
			}
		}
	}
	# validate fields when saved
	public static function validate_options($input) { return $input; }

	/*
	* Helper Functions
	*/
	
	# get the saved value for a setting, based on the option name we're given
	public static function get_option_value($setting, $choice = ''){
		# see if an option has been passed in (e.g. `cptd_options`)
		if($setting['option']){
			# if we're dealing with the default 
			if('cptd_options' == $setting['option']){
				$option = self::$options;
			}
			
			# if we have a custom option name or no option name
			else $option = get_option($setting['option']);
			if(!$option) return '';
			
			# if the option value is an array, get the desired setting
			if(is_array($option)){
				return array_key_exists($setting['name'], $option) 
					? $option[$setting['name']] 
					: (
						'' != $choice 
							?
							(
								array_key_exists($choice['id'], $option)
									? $option[$choice['id']]
									: ''
							)
							: ''
					);
			}
			# if option value is a string
			return $option;
		}
		# if no option is passed in, check post
		return CPTD_Helper::get_post_field($setting['name']);
	}
	# get the option name for a checkbox choice
	public static function get_choice_name($setting, $choice){
		if(!$setting['option']) return $choice['id'];
		return $setting['option'].'['.$choice['id'] . ']';
	}
}
# end class: CPTD_Options

/*
* Initialize static variables
*/

# settings sections
CPTD_Options::$sections = array(
	array(
		'name' => 'cptd_main', 'title' => '',
		'description' => '<p>Main Settings.</p>'
	),
);
# generate all settings for backend
CPTD_Options::$settings = array(
	
);

# get saved options
CPTD_Options::$options = (get_option('cptd_options') ? get_option('cptd_options') : array());
