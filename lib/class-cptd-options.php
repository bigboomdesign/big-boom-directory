<?php
class CPTD_Options{
	# Static variables are set after class definition
	## available settings
	static $settings;
	## saved options
	static $options = array();
	
	static $sections;
	static $default_section = 'cptdir_main';
		
	# Display field input
	static function do_settings_field($setting){
		$setting = CPTD::get_field_array($setting);
		# call one of several functions based on what type of field we have
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
			/* custom types for this plugin */
			case 'dropdown_pages':
				self::dropdown_pages($setting);
			break;
			/* end: custom types */
			default: self::text_field($setting);
		}
		if(array_key_exists('description', $setting)) {
		?>
			<p class='description'><?php echo $setting['description']; ?></p>
		<?php
		}
		/*
		* Custom content for this plugin
		*/
		# check if directory home and archive pages conflict
		if(
			$setting['name'] == 'front_page' 
				&& (
						CPTD::$pt
						|| CPTD::$ctax
						|| CPTD::$ttax
					)
		){
			# archives
			$pt_slug = CPTD::$pt->slug ? CPTD::$pt->slug : '';
			$ctax_slug = CPTD::$ctax->slug ? CPTD::$ctax->slug : '';
			$ttax_slug = CPTD::$ttax->slug ? CPTD::$ttax->slug : '';
			
			# directory home
			$front_page_id = CPTD_Options::$options['front_page'];
			$front_page = get_post($front_page_id);
			$page_slug = $front_page->post_name;
			
			#compare and give warning if necessary
			if($page_slug ==  $pt_slug){
			?>
				<p class='cptdir-fail'>Warning:</p>
				<p>Your directory home page has the same permalink as the post type URL slug.  Please change one of these values to avoid conflicts.</p>
			<?php
			}
			elseif($page_slug == $ctax_slug){
			?>
				<p class='cptdir-fail'>Warning:</p>
				<p>Your directory home page has the same permalink as the <?php echo CPTD::$ctax->sing; ?> taxonomy URL slug.  Please change one of these values to avoid conflicts.</p>
			<?php
			}
			elseif($page_slug == $ttax_slug){
			?>
				<p class='cptdir-fail'>Warning:</p>
				<p>Your directory home page has the same permalink as the taxonomy <?php echo CPTD::$ttax->sing; ?> URL slug.  Please change one of these values to avoid conflicts.</p>
			<?php			
			}
		}
		
		/*
		* end: custom content
		*/
		
		# Child fields (for conditional logic)
		if(array_key_exists('choices', $setting)){
			$choices = CPTD::get_choice_array($setting);
			# keep track of which fields we've displayed (in case two choices have the same child)
			$aKids = array();

			# Loop through choices and display and children
			foreach($choices as $choice){
				if(array_key_exists('children', $choice)){
					foreach($choice['children'] as $child_setting){
						# add this child to the array of completed child settings
						if(!in_array($child_setting['name'], $aKids)){
							$aKids[] = $child_setting['name'];
							# note the child field div is hidden unless the parent option is selected
						?><div 
							id="child_field_<?php echo $child_setting['name']; ?>"
							style="display: <?php echo (self::$options[$setting['name']] == $choice['value']) ? 'block' : 'none'?>"
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
	} # end function: do_settings_field
	## Text field
	static function text_field($setting){
		extract($setting);
		?><input 
			id="<?php echo $name; ?>" name="cptdir_options[<?php echo $name; ?>]" 
			class="regular-text<?php if(array_key_exists('class', $setting)) echo ' ' . $setting['class']; ?>" type='text' value="<?php echo self::$options[$name]; ?>" />
		<?php	
	}
	## Textarea field
	static function textarea_field($setting){
		extract($setting);
		?><textarea 
			id="<?php echo $name; ?>" name="cptdir_options[<?php echo $name; ?>]" 
			class="<?php if(array_key_exists('class', $setting)) echo $setting['class']; ?>"			
			cols='40' rows='7'><?php echo self::$options[$name]; ?></textarea>
		<?php
	}
	## Checkbox field
	static function checkbox_field($setting){
		extract($setting);
		foreach($choices as $choice){
		?><label class='checkbox' for="<?php echo $choice['id']; ?>">
			<input 
				type='checkbox'
				id="<?php echo $choice['id']; ?>"
				name="cptdir_options[<?php echo $choice['id']; ?>]"
				value="<?php echo $choice['value']; ?>"
				class="<?php if(array_key_exists('class', $setting)) echo $setting['class']; ?>"
				<?php checked(true, array_key_exists($choice['id'], self::$options)); ?>						
			/>&nbsp;<?php echo $choice['label']; ?> &nbsp; &nbsp;
		</label>
		<?php
		}
	}
	## <select> dropdown field
	static function select_field($setting){
		extract($setting);
	?><select 
		id="<?php echo $name; ?>"
		name="cptdir_options[<?php echo $name; ?>]"
		<?php if($class) echo "class='".$class."'"; ?>
	>
		<?php 
			# if we are given a string for $choices (i.e. single choice)
			if(is_string($choices)) {
				?><option 
					value="<?php echo CPTD::clean_str_for_field($choices); ?>"
					<?php selected(self::$options[$name], CPTD::clean_str_for_field($choice) ); ?>
				><?php echo $choices; ?>
				</option>
			<?php
			}
			# if $choices is an array
			elseif(is_array($choices)){
				foreach($choices as $choice){
					# if $choice is a string
					if(is_string($choice)){
						$label = $choice;
						$value = CPTD::clean_str_for_field($choice);
					}
					# if $choice is an array
					elseif(is_array($choice)){
						$label = $choice['label'];
						$value = isset($choice['value']) ? $choice['value'] : CPTD::clean_str_for_field($choice['label']);
					}
				?>
					<option 
						value="<?php echo $value; ?>"
						<?php selected(self::$options[$name], $value ); ?>					
					><?php echo $label; ?></option>
				<?php
				} # end foreach: $choices
			} # endif: $choices is an array
		?>
		
	</select><?php
	}
	## Radio Button field
	static function radio_field($setting){
		extract($setting);
		$choices = CPTD::get_choice_array($setting);
		foreach($choices as $choice){
				$label = $choice['label']; 
				$value = $choice['value'];
			?><label class='radio' for="<?php echo $choice['id']; ?>">
				<input type="radio" id="<?php echo $choice['id']; ?>" 
				name="cptdir_options[<?php echo $name; ?>]" 
				value="<?php echo $value; ?>"
				class="<?php if(array_key_exists('class', $setting)) echo $setting['class']; ?>"			
				<?php checked($value, self::$options[$name]); ?>
			/>&nbsp;<?php echo $label; ?></label>&nbsp;&nbsp;
			<?php
		}
	}	
	## Image field
	static function image_field($setting){
		# this will set $name for the field
		extract($setting);
		# current value for the field
		$value = self::$options[$name];		
		?><input 
			type='text'
			id="<?php echo $name; ?>" 
			class="regular-text text-upload <?php if(array_key_exists('class', $setting)) echo $setting['class']; ?>"
			name="cptdir_options[<?php echo $name; ?>]"
			value="<?php if($value) echo esc_url( $value ); ?>"
		/>		
		<input 
			id="media-button-<?php echo $name; ?>" type='button'
			value='Choose/Upload image'
			class=	'button button-primary open-media-button single'
		/>
		<div id="<?php echo $name; ?>-thumb-preview" class="cptdir-thumb-preview">
			<?php if($value){ ?><img src="<?php echo $value; ?>" /><?php } ?>
		</div>
		<?php
	}
	/* custom field types for this plugin */
	function dropdown_pages($setting){
		extract($setting);
		$args = array(
			"selected" => self::$options[$name],
			"name" => "cptdir_options[$name]",
		);
		if($show_option_none) $args['show_option_none'] = $show_option_none;
		wp_dropdown_pages($args);
	}	
	# Register settings
	static function register_settings(){
		# main option for this plugin
		register_setting( 'cptdir_options', 'cptdir_options', array('CPTD_Options', 'validate_options') );
		# add sections
		foreach(self::$sections as $section){
			add_settings_section(
				$section['name'], $section['title'], array('CPTD_Options', 'section_description'), 'cptdir_settings'
			);
		}
		# add fields
		foreach(self::$settings as $setting){
			add_settings_field($setting['name'], $setting['label'], array('CPTD_Options','do_settings_field'), 'cptdir_settings', ( array_key_exists('section', $setting) ? $setting['section'] : self::$default_section), $setting);
		}
	}
	function get_validation_callback($setting){
		$aSlugValidate = array("cpt_slug", "cpt_ctax_slug", "cpt_ttax_slug");
		if(in_array($setting, $aSlugValidate)) return array('CPTD_Options', 'validate_slug');
		return array('CPTD_Options', 'validate_options');
	}
	# validate fields when saved
	function validate_options($input){
		# need to validate slug fields to make sure we have a valid URL
		$aSlugValidate = array("cpt_slug", "ctax_slug", "ttax_slug");
		foreach($aSlugValidate as $key){
			if(array_key_exists($key, $input)){
				$input[$key] = CPTD::clean_str_for_url($input[$key]);
			}
		}
		return $input; 
	}
	function validate_slug($input){
		return CPTD::clean_str_for_url($input);		
	}	
	# Do settings page
	static function settings_page(){
		?><div class='wrap'>
			<h2>Custom Post Type Directory</h2>
			<p class="cptdir-success">Setting all 3 fields for the <b>Custom Post Type</b> is required for any other features to become active.</p>			
			<form action="options.php" method="post">
			<?php settings_fields('cptdir_options'); ?>
			<?php do_settings_sections('cptdir_settings'); ?>
			<?php submit_button(); ?>
			</form>
		</div><?php
	}
	# section description
	static function section_description($section){
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
	function fields_page(){
	?>
		<div class="wrap">
		<h2 class="cptdir-header">CPT Directory: Custom Fields</h2>
		<p><button id="map-custom-fields-to-acf" class="button button-primary">Map Fields</button></p>
		<p id="map-fields-message"></p>
		<p class='description'>If you define ACF fields after importing, you need to map the fields to ACF for your fields to auto-display on directory pages.</p>
		<?php 
		# Check if we have any custom fields to show 
		do{
			$aCF = CPTD::get_all_custom_fields();
			$aActiveFields = CPTD::get_all_custom_fields(true);
			# Iterate through custom fields we found and display table
			if(!$aCF) break;
			$bCF = true;
		?>    
			<div id="cptdir-edit-custom-fields">
		<?php
				foreach($aCF as $field) {    
		?>
					<div class="cptdir-edit-field">
						<h4 class="cptdir-header field"><?php echo $field; ?></h4>
						<h5 class="cptdir-header">Visibility</h5>
						<input type="checkbox" name="custom_field" value="<?php echo get_option('custom_field'); ?>" />&nbsp; Archives view<br />
						<input type="checkbox" name="custom_field" value="<?php echo get_option('custom_field'); ?>" />&nbsp; Single view
						<?php 
						# display warning if field is not being used
						if(!in_array($field, $aActiveFields)){ ?>
							<p class="cptdir-fail">This field doesn't seem to have any values in use.</p>
						<?php
						}
						?>
					</div>
				
			<?php
				} # end foreach: fields
			?>
			</div>
		<?php
		}
		while(0);
		if(!$bCF){ ?><p class="cptdir-fail">There aren't any custom fields associated with your post type yet.</p><?php }
		?>
		</div><?php # wrap	
	} # end: fields_page()
	function cleanup_page(){
		$pt = cptdir_get_pt();
		# whether or not we have any custom field data
		$bCF = false;
	?>
		<div class="wrap">
			<h2 class="cptdir-header">CPT Directory: Cleanup</h2>
			<p>While importing, you may want to clean up the database from time to time.  This page is intended to give you a fresh start with your post data and custom fields.</p>
			<p><b>Do not use these features if you have data for your Custom Post Type that you want to keep.</b></p>
			<hr />
			<h3>Remove Custom Field Data</h3>
			<?php
			# Check if we have any custom fields to show 
			do{
				$aCF = CPTD::get_all_custom_fields();
				$aActiveFields = CPTD::get_all_custom_fields(true);
				# Iterate through custom fields we found and display table
				if(!$aCF) break;
				$bCF = true;
			?>    
				<div id="cptdir-edit-custom-fields">
			<?php
					foreach($aCF as $field) {    
			?>
						<div class="cptdir-edit-field">
							<h4 class="cptdir-header field"><?php echo $field; ?></h4>
							<?php 
							# display warning if field is not being used
							if(!in_array($field, $aActiveFields)){ ?>
								<p class="cptdir-fail">This field doesn't seem to have any values in use.</p>
							<?php
							}
							?>
							<p><a data-field="<?php echo $field; ?>" id="cptdir-remove-<?php echo $field; ?>" class="cptdir-remove-field">Remove Field</a></p>
							<div id="cptdir-remove-<?php echo $field; ?>-message"></div>
						</div>
				
				<?php
					} # end foreach: fields
				?>
				</div>
			<?php
			}
			while(0);
			if(!$bCF){ ?><p class="cptdir-fail">There aren't any custom fields associated with your post type yet.</p><?php }
			else{
			?>		
				<div id='remove-custom-fields'>
					<h3>Remove ALL Custom Field Data for <b><?php echo $pt->pl; ?></b></h3>
					<p>This will clear all data from table <kbd>wp_postmeta</kbd> for your post type.</p>
					<div id='cptdir-remove-all-fields-messsage'></div>
					<p><button class='button button-primary' id='remove-all-postmeta'>Clear ALL Field Data</button></p>
				</div>
			<?php
			}
			?>
			<hr />
			<div>
				<h3>Remove Posts and Drafts</h3>
				<p>Remove drafts, revisions, etc. from <kbd>wp_posts</kbd> for <b><?php echo $pt->pl; ?></b></p>
				<div id='cptdir-remove-unpublished-messsage'></div>			
				<p><button id="remove-unpublished" class='button button-primary'>Remove</button></p>			
				<hr />
				<p>Remove published <b><?php echo $pt->pl; ?></b> from <kbd>wp_posts</kbd></p>
				<div id='cptdir-remove-published-messsage'></div>
				<p><button id='remove-published' class='button button-primary'>Remove</button></p>			
			</div>
		</div>
	<?php	
	} # end: cleanup_page()
	function import_page(){ 
		require_once cptdir_dir("lib/class-cptd-import.php"); 
		$importer = new CPTD_import( cptdir_get_pt(), cptdir_get_cat_tax(), cptdir_get_tag_tax() );
		$importer->do_import_page();
	}	
}
# Initialize static variables
## settings sections
CPTD_Options::$sections = array(
	array(
		'name' => 'cptdir_main'
	),
	array(
		'name' => 'cptdir_pt', 'title' => 'Custom Post Type',
		'description' => ''
	),
	array(
		'name' => 'cptdir_ctax', 'title' => 'Custom Taxonomy (Heirarchical)',
		'description' => 'Behaves like <em>categories</em>'
	),
	array(
		'name' => 'cptdir_ttax', 'title' => 'Custom Taxonomy (Non-Heirarchical)',
		'description' => 'Behaves like <em>tags</em>'
	),
	array(
		'name' => 'cptdir_search', 'title' => 'Search'
	),
);
## generate all settings
CPTD_Options::$settings = array(
	# main
	## the following are added after post type setup
		# `front_page`
		# `front_page_shows`
		# `front_page_show_empty`
	
	# custom post type
	array(
		'name' => 'cpt_sing', 'label' => 'Singular Label',
		'section' => 'cptdir_pt'
	),
	array(
		'name' => 'cpt_pl', 'label' => 'Plural Label',
		'section' => 'cptdir_pt'
	),
	array(
		'name' => 'cpt_slug', 'label' => 'SEO Friendly Slug (e.g. /custom-post-type)',
		'section' => 'cptdir_pt',
		'description' => 'Make sure to Save your Permalink Settings after changing this value'
	),
	# custom taxonomies
	## heirarchical
	array(
		'name' => 'ctax_sing', 'label' => 'Singular Label',
		'section' => 'cptdir_ctax'
	),
	array(
		'name' => 'ctax_pl', 'label' => 'Plural Label',
		'section' => 'cptdir_ctax'
	),
	array(
		'name' => 'ctax_slug', 'label' => 'SEO Friendly Slug (e.g. /custom-taxonomy)',
		'section' => 'cptdir_ctax',
		'description' => 'Make sure to Save your Permalink Settings after changing this value'		
	),
	## non-heirarchical
	array(
		'name' => 'ttax_sing', 'label' => 'Singular Label',
		'section' => 'cptdir_ttax'
	),
	array(
		'name' => 'ttax_pl', 'label' => 'Plural Label',
		'section' => 'cptdir_ttax'
	),
	array(
		'name' => 'ttax_slug', 'label' => 'SEO Friendly Slug (e.g. /custom-taxonomy)',
		'section' => 'cptdir_ttax',
		'description' => 'Make sure to Save your Permalink Settings after changing this value'
		
	),
	# search
	array(
		'name' => 'search_page', 'type' => 'dropdown_pages',
		'label' => 'Results page for widget search',
		'section' => 'cptdir_search',
		'show_option_none' => 'Select page for widget search results'
	),
);

## get saved options
CPTD_Options::$options = get_option('cptdir_options');