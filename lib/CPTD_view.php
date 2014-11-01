<?php
class CPTD_view{
	var $ID = 0;
	var $type = "";
	
	var $fields = array();
	var $acf_fields = array();
	
	function __construct($args = array()){
		# Loop through arguments and set object variables
		foreach($args as $arg => $val){
			if(property_exists($this, $arg)) $this->$arg = $val;
		}
	} # end: __construct()
	
	# display fields for listing
	function do_fields(){
		if(!$this-ID) return;
		
		# if ACF is activated
		if(function_exists("get_fields")){
			$fields = get_fields($this->ID);
			$fields = CPTDirectory::filter_post_meta($fields);
			$ordered_fields = array();
			
			# order the fields
			foreach($fields as $field => $value){
				$aField = get_field_object($field);
				if($aField['order_no'] >= 0) $ordered_fields[$aField['order_no']] = $aField;
			}
			ksort($ordered_fields);
			
			# loop through fields and display label & value
			if($ordered_fields){
				?><div class="cptdir-fields-wrap"><?php
					foreach($ordered_fields as $field){
					?><div class="cptdir-field <?php echo $field['type'] . " " . $field['name']; ?>">
						<label><?php echo $field['label'];?>: &nbsp; </label><?php echo $field["value"]; ?>
					</div>
					<?php
					}
				?></div><?php
			} #end if: fields exist
		}
		# work in progress: plugin behavior without ACF
		else $fields = CPTDirectory::get_fields_for_listing($this->ID);
	}
} # end class: CPTD_view