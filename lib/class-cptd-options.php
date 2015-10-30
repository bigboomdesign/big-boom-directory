<?php
/**
 * Handles the display, saving, and init/retrieval of options for the plugin
 *
 * Static variables are set after class definition below
 *
 * @since 2.0.0
 */
class CPTD_Options{

	/**
	 * Class parameters
	 */

	/**
	 * The available settings for the plugin.
	 *
	 * See `do_settings_field()` for a description of a typical element of the array
	 * 
	 * @param 	array 	$settings{
	 *			@type array ...,
	 * 			@type array ...,
	 *			...
	 * }
	 * @since 	2.0.0
 	 */
	static $settings = array();

	/**
	 * Options saved by the user.
	 *
	 * In addition to the defaults below, the array contains a key/value pair 
	 * corresponding to each self::$settings element and the user-selected value
	 *
	 * Default values:
	 *
	 * @param 	array 	$options{
	 *
	 * }
	 * @since 	2.0.0
	 */
	static $options = array();
	
	/**
	 * The sections to display on the plugin settings page
	 *
	 * Used for WP's `add_settings_section()` and in the corresponding callback function
	 *
	 * @param	array	$sections{
	 * 		@type 	string	$name 			Optional. The section named used in `add_settings_section()` (Default: self::$default_section )
	 *		@type 	string 	$title 			Optional. The title displayed in the section's header
	 * 		@type 	string 	$description 	Optional. A description for the section
	 * }
	 * @since 	2.0.0
	 */
	static $sections = array();

	/**
	 * The default section to use for a setting if none is specified
	 * @param 	string
	 * @since 	2.0.0
	 */
	static $default_section = 'cptd_main';

	/**
	 * Whether or not the defaults have been loaded
	 */
	static $options_initialized = false;
		
	/**
	 * Class methods
	 */

	/**
	 * Display a plugin settings form element
	 *
	 * @param 	string|array 	$setting{
	 *
	 *		Use a string for simple fields. Use an array to pass detailed information about the
	 *		setting.  Optional types will be auto-completed via `CPTD::get_field_array()`
	 *
	 *		@type 	string 			$label 			Required. The label for the form element	 
	 * 		@type 	string 			$name 			Optional. The HTML name attribute. Will be auto-generated from label if empty
	 * 		@type 	string 			$id 			Optional. The HTML `id` attribute for the form element. Will be auto-generated from label if empty
	 *		@type 	string 			$type 			Optional. The type of form element to display (text|textarea|checkbox|select|single-image|radio) (Default: 'text')
	 *												Use a custom $type and define a method on `self` with the same name to automatically link the field display handler
	 * 		@type 	string 			$value 			Optional. The value of the HTML `value` attribute
	 * 		@type 	array|string 	$choices 		Optional. The choices for the form element (for select, radio, checkbox)
	 * 		@type	string			$class 			Optional. The HTML `class` attribute for the form element
	 * 		@type 	string			$label_class	Optional. For checkboxes and radio buttons, a class can be applied to each choice's label
	 * 		@type 	array 			$data 			Optional. An array of data attributes to add to the form element (see `self::data_atts()`)
	 * }
	 *
	 * @param	string	$option 	Optional (Default: 'cptd_options'). By default, an HTML input element whose name is `form_field`
	 *								will actually have a name attribute of `cptd_options[form_field]`. Pass in a string to 
	 *								change the default parent field name, or pass an empty string to use a regular input name without a parent
	 * @since 	2.0.0
	 */
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
	
	/**
	 * Display a text input element
	 *
	 * @param 	array 	$setting 	See `do_settings_field()`. Has been filtered through `RO3::get_field_array()`
	 * @since 	2.0.0
	 */
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
	
	/**
	 * Display a textarea element
	 * @param 	array 	$setting 	See `do_settings_field()`. Has been filtered through `RO3::get_field_array()`
	 * @since 	2.0.0
	 */
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
	
	/**
	 * Display one or more checkboxes
	 * @param 	array 	$setting 	See `do_settings_field()`. Has been filtered through `RO3::get_field_array()`
	 * @since 	2.0.0
	 */
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
	
	/**
	 * Display a group of radio buttons
	 * @param 	array 	$setting 	See `do_settings_field()`. Has been filtered through `RO3::get_field_array()`
	 * @since 	2.0.0
	 */
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
	
	/**
	 * Display a <select> dropdown element
	 * @param 	array 	$setting 	See `do_settings_field()`. Has been filtered through `RO3::get_field_array()`
	 * @since 	2.0.0
	 */
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
	
	/**
	 * Display an image upload element that uses the WP Media browser
	 * @param 	array 	$setting 	See `do_settings_field()`. Has been filtered through `RO3::get_field_array()`
	 * @since 	2.0.0
	 */
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

	/**
	 * Matching a function name with the respecting $setting['type'] allows for creation of "on the fly" options.
	 * In this case, we have defined a setting with type `on_the_fly` which automatically triggers
	 * the callback below.  Replace and duplicate as needed.
	 * 
	 * The main benefit is that no special case needs to be added in the main switch 
	 * statement in `self::do_settings_field()`
	 * 
	 * @param 	array 	$setting 	See `do_settings_field()`. Has been filtered through `RO3::get_field_array()`
	 * @since 	2.0.0
	 */
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
	
	/**
	 * Return a string of HTML data attributes for a field or choice input element
	 *
	 * @param	array 	$setting{
	 *		A $setting array (see `do_settings_field()`) or a $choice array, which ostensibly
	 * 		has a `data` key with corresponding hash of data attributes
	 *
	 *		@type array  $data{
	 *			Any key/value pair you like can be added to this array when defining settings
	 *
	 *			@type string $var 	A value to be added for the HTML data attribute `data-var`
	 * 		}
	 * @return 	string
	 * @since 	2.0.0
	 */
	 public static function data_atts($setting){
		if(!array_key_exists('data', $setting)) return;
		$out = '';
		foreach($setting['data'] as $k => $v){
			$out .= "data-{$k}='{$v}' ";
		}
		return $out;
	} # end: data_atts()
	
	/**
	 * Initializes plugin settings whose choices may depend on WP data
	 * Registers the main option to be stored in the database and adds its sections and fields
	 * Loads default values for the plugin settings 
	 *
	 * @since 	2.0.0
	 */
	public static function register_settings(){

		# initialize the settings that depend on WP data if necessary
		self::initialize_settings();

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

	/**
	 * Initialize any plugin settings that may depend on WP data
	 *
	 * - Image sizes for archive and single views
	 *
	 * @since 	2.0.0
	 */
	public static function initialize_settings() {

		# don't initialize settings twice
		if( self::$options_initialized ) return;

		# set the repeat indicator to true
		self::$options_initialized = true;

		/**
		 * Image sizes for archive and single views
		 */

		$image_sizes = CPTD_Helper::get_image_sizes();

		# archive image size option
		CPTD_Options::$settings[] = array(
			'name' => 'image_size_archive',
			'label' => 'Image size for archive view',
			'type' => 'select',
			'choices' => $image_sizes,
			'default' => 'thumbnail',
			'description' => 'Applies to ACF fields with type `image`'
		);

		# single image size option
		CPTD_Options::$settings[] = array(
			'name' => 'image_size_single',
			'label' => 'Image size for single view',
			'type' => 'select',
			'choices' => $image_sizes,
			'default' => 'medium',
			'description' => 'Applies to ACF fields with type `image`'
		);
		
		self::load_default_settings();

	} # end: initialize_settings()

	/**
	 * Load default values for the plugin's settings
	 *
	 * @since 	2.0.0
	 */
	public static function load_default_settings() {

		# only allow defaults for checkboxes if we truly have no saved options
		$allow_for_checkboxes = false;

		if( empty( CPTD_Options::$options ) ) $allow_for_checkboxes = true;

		foreach( CPTD_Options::$settings as $setting ) {

			$default = '';
			if( ! empty( $setting['default'] ) ) $default = $setting['default'];

			# for checkboxes
			if( 'checkbox' == $setting['type'] ) {

				if( ! $allow_for_checkboxes ) continue;

				# checkboxes have on large key in the form `setting_name`_`setting_value`
				if( $default )  CPTD_Options::$options[ $setting['name'].'_'.$default ] = $default;

				continue;
				
			} # end: checkbox fields

			# all other fields
			if( ! isset ( CPTD_Options::$options[ $setting['name'] ] ) ) CPTD_Options::$options[ $setting['name'] ] = $default;
		}

		#CPTD_Options::$options['image_size_single'] = 'medium';
	} # end: load_default_settings()
	
	/**
	 * Display the description for a setting (callback for WP's `add_settings_section`)
	 * @since 	2.0.0
 	 */
	public static function section_description($section){
		?>
		<hr />
		<?php
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

	/**
	 * Validate fields when saved (callback for WP's `register_setting`)
	 * @since 	2.0.0
	 */
	public static function validate_options($input) { return $input; }

	/**
	 * Helper Functions
	 *
	 * - get_option_value()
	 * - get_choice_name()
	 */
	
	/**
	 * Get the saved value for a setting, based on the option name we're given
	 * 
	 * @param  	array 	$setting 	The setting to get the value for (see `do_settings_field`)
	 * @param  	array 	$choice 	The particular choice to get the value for if necessary
	 * @return 	string
	 * @since 	2.0.0
	 */	
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

	/**
	 * Get the name attribute for a checkbox choice based on its parent option
	 *
	 * @param 	array 	$setting 	The parent setting (see `do_settings_field()`)
	 * @param 	array 	$choice 	The choice to get the name attribute for
	 * @return 	string
	 * @since 	2.0.0
	 */
	public static function get_choice_name($setting, $choice){
		if(!$setting['option']) return $choice['id'];
		return $setting['option'].'['.$choice['id'] . ']';
	}
}
# end class: CPTD_Options

/**
 * Initialize static variables
 *
 * - Settings sections for plugin options page
 * - Settings for plugin options page (these serve as defaults for new CPTD post types)
 */

# Settings sections for plugin options page
CPTD_Options::$sections = array(
	array(
		'name' => 'cptd_main', 'title' => 'Post type defaults',
		'description' => '<p>These options serve as the default settings for new post types you create using this plugin.</p>'
	),
);

/**
 * Settings for plugin options page (these serve as defaults for new CPTD post types)
 * 
 * - Default post orderby
 * - Default post order
 * - Meta key to order by
 * - Auto detect website field
 * - Auto detect social media fields
 * - Additional settings that depend on WP data are called via self::register_settings, which fires on admin_init
 */
CPTD_Options::$settings = array(

	# Default post orderby
	array(
		'name' 		=> 'post_orderby',
		'label' 	=> 'Order Posts By',
		'type' 		=> 'select',
		'choices' 	=> array(
			array( 'value' => 'title', 'label' => 'Post Title' ),
			array( 'value' => 'meta_value', 'label' => 'Custom Field' ),
			array( 'value' => 'meta_value_num', 'label' => 'Custom Field (Numerical)' ),
			array( 'value' => 'date', 'label' => 'Post Date' ),
			array( 'value' => 'rand', 'label' => 'Random' ),
		),
		'default' 	=> 'title',
	),

	# Meta key to order by, if 'post_orderby' = ( 'meta_value' | 'meta_value_num' )
	array(
		'name'		=> 'meta_key_orderby',
		'label'		=> 'Field key to use for ordering posts',
		'type'		=> 'text',
		'description' 	=> 'Use a field key like <code>last_name</code>. Posts with no value for the field will not appear in results.',
		'default'		=> 'title',
	),

	# Default post order
	array(
		'name'		=> 'post_order',
		'label' 	=> 'Post Order',
		'type' 		=> 'select',
		'choices'	=> array(
			array( 'value' => 'ASC', 'label' => 'Ascending' ),
			array( 'value' => 'DESC', 'label' => 'Descending' ),
		),
		'default' => 'ASC',
	),

	# Auto detect website field
	array(
		'name' => 'auto_detect_url',
		'label' => 'Auto Detect Website Field',
		'type' => 'checkbox',
		'choices' => 'Yes',
		'default' => 'yes',
		'description' => 'Displays "View Website" link text for `web`, `website`, and `url` fields',
	),

	# Auto detect social media fields
	array(
		'name' => 'auto_detect_social',
		'label' => 'Auto Detect Social Media Fields',
		'type' => 'checkbox',
		'choices' => 'Yes',
		'default' => 'yes',
		'description' => 'Uses icons for `facebook`, `twitter`, `linkedin`, `instagram`, `pinterest`, `google_plus` fields'
	),
);

# get saved options
CPTD_Options::$options = ( $option = get_option('cptd_options' ) ) ? $option : array();
