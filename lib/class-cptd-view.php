<?php
class CPTD_view{
	var $ID = 0;
	var $type = "";
	
	var $fields = array();
	var $acf_fields = array();

	/**
	 * Social fields that we will autodetect and replace with font-awesome icons
	 */
	var $social_fields = array( 'facebook', 'twitter', 'linkedin', 'instagram', 
		'pinterest', 'google_plus'
	);

	/**
	 * Social fields that actually exist in ACF, so we know how many to look for
	 */
	var $active_social_fields = array();

	/**
	 * Whether or not we have displayed any social icons
	 *
	 * This is necessary since social icons should be orderable but still need to all be wrapped 
	 * in a div for styling.  We won't necessarily know what the first/last social icons are
	 */
	var $done_social = false;

	/**
	 * An array of social icons that we have looped through
	 *
	 * When all social fields are done, we need to close the container div
	 */
	var $social_fields_completed = array();
	
	function __construct($args = array()){
		# Loop through arguments and set object variables
		foreach($args as $arg => $val){
			if(property_exists($this, $arg)) $this->$arg = $val;
		}
	} # end: __construct()
	# single field display
	public function do_single_field($field, $echo = true){

		ob_start();

		/**
		 * Special cases
		 */

		# social fields: show an icon instead of text
		$bSocial = false;

		#var_dump($field); echo '<hr />';

		if( in_array( $field['name'], $this->social_fields ) ) {
			$bSocial = true;
			$this->social_fields_completed[] = $field['name'];

			if( '' == $field['value'] ) return;

			# open the wrapping div if this is the first social icon
			if( ! $this->done_social ) {
				$this->done_social = true;
			?>
				<div class='cptdir-social-fields'>
			<?php
			} # end if: first social icon
			?>
					<a target="_blank" href="<?php echo $field['value']; ?>"><i class="fa fa-<?php echo str_replace('_','-',$field['name']); ?>" ></i></a>
			<?php

			# check if we're done with all social icons
			if( count( $this->social_fields_completed ) == count( $this->active_social_fields ) ) {
			?>
				</div>
			<?php
			} # end if: last social icon

			return;
		} # end if: social field

		if(empty($field['value'])) return;

		global $post;

		# for image fields
		if($field["type"] == "image"){

			$src = "";

			# ACF gives the option of multiple save formats for images (object/url/id)
			switch($field['save_format']){
				case "object":
				case "url":
					# if set to return an object, we'll have an array as the value
					# otherwise we'll have the URL string
					$src = is_array($field['value']) ? $field['value']['url'] : $field['value'];
				break;
				case "id";
					$src = wp_get_attachment_url($field['value']);
				break;
			}

			# show image if we have a src
			if( $src ) {
				# make the image link to the listing page if we are on an archive page or search results view
				$link = '';
				if( is_archive() || is_search() ) {
					global $post;
					$link = get_permalink($post->id);
				}
			?>
				<div class='cptdir-image-container'>
				<?php
				if( $link ) {
				?>
					<a href="<?php echo $link; ?>">
				<?php
				}
				?>
						<img class="cptdir-image <?php echo $field['name' ]; ?>" 
								src="<?php echo $src; ?>" 
						/>
				<?php
				if( $link ) {
				?>
					</a>
				<?php
				}
				?>
				</div>
			<?php 
			} # end if: image source is set

			# go to next field after showing the image
			return;
		} # endif: image field

		elseif($field["type"] == "gallery"){
			if($field["value"]){
			?><div class="cptdir-gallery <?php echo $field['name']; ?>"><?php
				foreach($field["value"] as $i => $img){
					$thumb = $img['sizes']['thumbnail'];
				?>
					<a class="cptdir-gallery-thumb <?php echo $i; ?>" href="<?php echo $img['url']; ?>" data-lightbox="gallery-<?php echo $field['name']; ?>"><img class="cptdir-image <?php echo $field['name'] . " " . $i; ?>" src="<?php echo $thumb; ?>" /></a>
				<?php
				} #end foreach: gallery image
			?></div>
			<?php
			} # endif: value exists

			# go to next field after showing the gallery
			return;
		} # end if: gallery field type
		
		# field wrapper for text-based fields
		# apply filter to value so users can edit it
		$field = apply_filters('cptd_field_value_'.$field['name'], $field);
		if($field['value']){
		?><div class="cptdir-field <?php echo $field['type'] . " " . $field['name']; ?>">
			<label><?php echo $field['label'];?>: &nbsp; </label><?php echo $field["value"]; ?>
		</div>
	<?php
		}
		$html = ob_get_contents();
		ob_end_clean();
		if($echo) echo $html;
		else return $html;
	} # end: do_single_field()

	# display fields for listing
	function do_fields($callback = ""){
		if(!$this->ID) return;
		$fields = array();
		# if ACF is activated
		if(function_exists("get_fields")){
			$fields = get_fields($this->ID);
			if(!$fields) return;
			$fields = CPTD::filter_post_meta($fields, true);
			$ordered_fields = array();
			
			# order the fields
			foreach($fields as $field => $value){

				$aField = get_field_object($field);

				if( '' == $aField['name'] ) continue;
				
				if(isset($aField['order_no']) && $aField['order_no'] >= 0) {

					$ordered_fields[$aField['order_no']] = $aField;

					# if this is a social field, add to the active array
					if( in_array( $aField['name'], $this->social_fields ) ) {
						$this->active_social_fields[] = $aField['name'];
					}
				}
			}
			ksort($ordered_fields);

			# If callback is set, filter out fields using user-specified conditions
			if(function_exists($callback)){$ordered_fields = array_filter($ordered_fields, $callback);}
						
			# loop through fields and display label & value
			if($ordered_fields){
				?><div class="cptdir-fields-wrap"><?php
					foreach($ordered_fields as $field){
						do_action('cptd_pre_render_field_'.$field['name'], $field);
						$this->do_single_field($field);
						do_action('cptd_post_render_field_'.$field['name'], $field);
					} # end foreach: fields
				?></div><?php
			} #end if: fields exist
		}
		# work in progress: plugin behavior without ACF
		if(!$fields) $fields = CPTD::get_fields_for_listing($this->ID);
	}	
} # end class: CPTD_view