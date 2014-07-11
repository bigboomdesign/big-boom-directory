<?php
class CPTD_import{
	var $post_type;
	var $ctax;
	var $ttax;
	# Headers from csv file
	var $headers = array();
	# Rows from CSV file
	var $rows = array();
	var $num_rows = 0;
	# Has file been posted?
	var $bFile;
	# Has form been posted?
	var $bForm;
	var $post_fields = array(
		  array("key" => 'post_title', "label" => "Post Title", "type"=>'post_field'),
		  array("key" => 'post_name', "label" => "Post Slug/Permalink", "type" => 'post_field'),
		  array("key"=> 'post_content', "label" => "Post Content", "type"=> 'post_field'),
	);
	var $column_options = array();
	var $column_map = array();
	function __construct($pt, $ctax, $ttax){
		$this->post_type = $pt;
		$this->ctax = $ctax;
		$this->ttax = $ttax;
		$this->bFile = array_key_exists("cptdir-import-file", $_FILES) && !empty($_FILES["cptdir-import-file"]);
		$this->bForm = array_key_exists("cptdir-import-submit", $_POST);
		
		# Get options for csv columns
		$custom_options = $this->get_custom_column_options();
		$this->column_options = array_merge($this->post_fields, $custom_options);
		
		# If post is set, create the user-generated column map
		if($this->bFile && $this->bForm) $this->create_column_map();
	}
	public function do_import_page(){
	?>
	<div class="wrap">
		<h2 class="cptdir-header">CPT Directory: Import</h2>
		<form method="post" enctype="multipart/form-data" id="cptdir-file-select">
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Import File (must be .csv)</th>
					<td><input type="file" name="cptdir-import-file" id="cptdir-import-file">
					<br/><p style="display: none;" id="cptdir-file-message" class="cptdir-fail"></p>
					</td>
				</tr>             
			</table>
			<?php submit_button("Submit File", "primary", "cptdir-file-submit"); ?>
		</form>
		<div id="cptdir-import-content">
		<?php 
		if($this->bFile || $this->bForm) $this->do_import_content();
		
		?>
		</div>
	</div>
	<?php
	}
	# This is called via AJAX so we use POST data within
	public function do_import_content(){
		# Check for uploaded file without field map form
		if($this->bFile && !$this->bForm)
		do{
			$this->parse_csv();
			if(!$this->headers){ cptdir_fail("No rows found."); break; }
			echo cptdir_success($this->num_rows." rows found", "h4", "cptdir-header");
			echo "<hr class='cptdir-hr' />";
			if(array() !== $this->headers){
			?>
				<form id="cptdir-import-form" name="cptdir-import-form" method="post" enctype="multipart/form-data">				
					<h4 class="cptdir-header">We found the following column headers</h4>
					<p>Please select the target destination for the data in each column of your file.</p>						
					<?php 
					foreach($this->headers as $header){
					?>
						<div class="cptdir-found-header">
							<h5 class="cptdir-header"><?php echo $header; ?></h5>
							<?php $this->do_fields_dropdown( CPTDirectory::str_to_field_name($header) ); ?>
						</div>
						<hr class="cptdir-hr" />
					<?php
					} # end foreach: headers
					submit_button("Import", "primary", "cptdir-import-submit");
				?>
				</form>
				<?php
			}
		} while(0); # endif: file is posted
		# Check if file and form are both posted
		elseif($this->bFile && $this->bForm){
			$this->run_import($bDebug);
		} # end: file and form are posted
	}
	# Load headers, rows, and row count into object	
	private function parse_csv(){
		if(!$this->bFile) return false;
		$file = $_FILES["cptdir-import-file"];
		# open file
		if(!($fh = fopen($file["tmp_name"], "r"))){ cptdir_fail("Couldn't read file."); return false;}
		# Count rows		
		$i = 0;
		$headers = array();
		$rows = array();
		# Loop through rows
		while(!feof($fh)){
			$row = fgetcsv($fh, 0, ",");
			if($i == 0){
				$headers = $row;
			}
			else{
				if(array() == $headers) return false;
				# Test if row is empty.
				$bEmpty = true;
				# Loop through headers and create associative array for row
				foreach($headers as $n => $header){
					if("" != $row[$n]) $bEmpty = false;
					$my_row[ CPTDirectory::str_to_field_name($header) ] = $row[$n];
				}
				# If row is not empty, pass it into the rows array
				if(!$bEmpty) $rows[] = $my_row;
			}
			$i++;
		}
		$this->headers = $headers;
		$this->rows = $rows;
		$this->num_rows = $i;		
	}
	# Get all options for csv columns
	public function get_custom_column_options(){
		$aFields = array();
		$aCF = CPTDirectory::get_all_custom_fields();
		$custom_fields = array();
		foreach( $aCF as $field ){ $aFields[] = array( "key" => $field, "label" => "Custom Field: $field", "type"=>"custom_field"); }
		#$aFields = array_merge(self::$post_fields, $custom_fields);
		if($this->ttax) $aFields[] = array("key"=> $this->ttax->name, "label" => "Taxonomy: " . $this->ttax->name, "type"=>'ttax');
		if($this->ctax) $aFields[] = array("key"=> $this->ctax->name, "label" => "Taxonomy: " . $this->ctax->name, "type"=>'ctax');	
		return $aFields;
	}
	# Create map from column_options field to csv_fields based on POST input
	private function create_column_map(){
		foreach( $_POST as $k => $v){
			$key = CPTDirectory::san($k);
			$val = CPTDirectory::san($v);
			$matches = array();
			$csv_field = "";
			$import_field = "";
			$bValid = false;
			if(preg_match("/cptdir-import-(.*)/", $key, $matches)){
				$csv_field = $matches[1];
				if("submit" == $csv_field) continue;
				$import_field = $val;
				# Check if this is a valid choice
				foreach($this->column_options as $aOption){
					if($import_field == $aOption['key']){ $bValid = true; continue; }
				}
				if($bValid){ 
					$this->column_map[$import_field] = $csv_field;
				}
			}
		}
		foreach($this->column_options as $k => $aOption) if(!array_key_exists($aOption["key"], $this->column_map)) $this->column_map[$aOption["key"]] = "";
	}
	# Display dropdown, given fields and slug for the <select > name/ID
	public function do_fields_dropdown($slug){
		if(array() == $this->column_options) return false;
		?>
		<select autocomplete="off" id="cptdir-import-<?php echo $slug; ?>" name="cptdir-import-<?php echo $slug; ?>" class="cptdir-import-select" data-field="">
			<option value="">Select existing field</option>
		<?php
			foreach($this->column_options as $field){
			?>
				<option value="<?php echo $field["key"]; ?>" class="cptdir-import-option-<?php echo $slug ?>"><?php echo $field["label"]; ?></option>
			<?php
			}
		?>
		</select>
		<div class="cptdir-cf-or">OR</div>
		<div class="cptdir-new-cf">
			<input type="checkbox" name="cptdir-new-cf-<?php echo $field_name; ?>" id="cptdir-new-cf-<?php echo $field_name; ?>" data-field="<?php echo $field_name; ?>"/> Create new custom field: 
			&nbsp;<b> <?php echo $field_name; ?></b>
		</div>
		<?php
	}
	# Run the main import process, inputting whether or not we are in debug mode
	private function run_import($bDebug = false){
		if(!$this->bFile || !$this->bForm) return false;
		# $bDebug = true;
		if($bDebug){ echo "<h3 class='debug'>POST: </h3>"; var_dump($_POST); echo "<h3 class='debug'>Files: </h3>"; var_dump($_FILES); }
		$this->parse_csv();		
		# if( $nImport_rows > 0 ) {
		if( $this->num_rows > 0) {
			echo "<h3 class='cptdir-header'>Attempting to import " . $this->num_rows . " rows...</h3>";

			/**********
			Grab existing permalinks from Entries With Given Post Type, and then all Taxonomy Terms with given Custom Taxonomy
			Used to avoid creating duplicate entries.	
			**********/

			// Collect Existing Permalinks
			$aPosts = CPTDirectory::get_all_cpt_posts();
			$aPost_names = array();
			foreach($aPosts as $post) $aPost_names[] = $post->post_name;
			if($bDebug){ echo "<h3 class='debug'>Existing Posts: </h3>"; var_dump($aPost_names); echo "<br />";}

			// Collect Existing Taxonomy Terms
			$aCtax_terms = get_terms($this->ctax->name);
			$aTtax_terms = get_terms($this->ttax->name);
			if($bDebug){
				echo "<h3 class='debug'>Heirarchical Terms: </h3>";
				var_dump($aCtax_terms);
				echo "<br /><br />";
				echo "<h3 class='debug'>Non-Heirarchical Terms: </h3>";
				var_dump($aTtax_terms);
				echo "<br /><br />";
			}
			/***
			* Loop through rows
			***/
			
			# Count total successes and fails for rows
			$nPost_success = 0;
			$nPost_fail = 0;
			foreach( $this->rows as $csvRow ) {			
				# Are we updating?
				$bPost_exists = false;
				# Existing post, if applicable
				$oPost = "";
				// Initialize Success Indicators for General Post Data, Custom Fields, and Custom Taxonomy Terms
				$bPost_success = false;

				$nCF_success = 0;
				$nCF_fail = 0;
				
				# Set up row with associated column_options keys
				$aRow = array();
				foreach($this->column_map as $k => $v){
					if(!array_key_exists($v, $csvRow)) continue;
					$aRow[$k] = $csvRow[$v]?$csvRow[$v]:"";
				}
				# Skip if we have no title
				if(!$aRow["post_title"]){ cptdir_fail("No title was found."); $nPost_fail++; continue; }
								
				# Set slug based on title if it doesn't already exist
				if(!$aRow["post_name"]) $aRow["post_name"] = CPTDirectory::clean_str_for_url($aRow["post_title"]);
								
				echo '<hr />';
				echo '<h4 class="cptdir-header">Importing Post: <span style="color: midnightblue;">' . $aRow['post_title'] . '</b></h4>';
				
				if($bDebug){ 
					echo "<h4 class='debug'>CSV Row Data:</h4>"; var_dump($csvRow);
					echo "<h4 class='debug'>Import Row Data:</h4>"; var_dump($aRow); echo "<br /><br />";
				}
				
				# Check if permalink is free for the taking
				echo "<p>Formatted Title: <b>'" . $aRow["post_name"] . "'</b></p>";				
				if( !in_array( $aRow["post_name"] , $aPost_names ) ) { echo "<p class='cptdir-success'>Permalink is available</p>"; }
				# If permalink exists, get the post object we will be updating
				else{ 
					$bPost_exists = true;
					echo "<p><span class='cptdir-fail'>Already Exists</span>: Attempting to update post data</p>"; 
					foreach($aPosts as $post){ if($post->post_name == $aRow["post_name"]) $oPost = $post;  }
					if($bDebug){ echo "<h4 class='debug'>Post to Update</h4>"; var_dump($oPost); echo "<br /><br />"; }
				}
								
				
				/***
				* Post Object Data
				***/
				
				# Right now, let's just focus on one or two posts!
				#if(!($aRow["post_title"] == "Hawkins Oak" || $aRow["post_title"] == "Murdock Pecan")) continue;
				$post_args = array();
				$post_args = array(
					'post_title'     => $aRow["post_title"], //$aRow['post_title'],
					'post_status'           => 'publish', 
					'post_type'             => $this->post_type->name,
					'post_content'   => $aRow["post_content"],
					'post_name'      => $aRow["post_name"],					  
				);
				# Add ID to the arguments if post exists, so that an update is done instead of an insert
				if($bPost_exists && is_object($oPost)) $post_args["ID"] = $oPost->ID;
				$new_id = wp_insert_post($post_args);
				
				# Successful if ID was returned from post insert
				if($new_id){
					$bPost_success = true;
					$nPost_success++;
					 echo cptdir_success( ($bPost_exists?"Updated":"Created") . " post with ID " . $new_id);
				}
				# Continue to next row if we failed
				else{ echo cptdir_fail("There was a problem with this post."); $nPost_fail++; continue; }
				
				/***
				* Custom Fields
				***/	

				echo "<b>Attempting to Importing Custom Field Information...</b><br />";
				if($bDebug) echo "<h4 class='debug'>Custom Field Data:</h4>";
				# Loop through all column options
				foreach( $this->column_options as $aField  ){
					$value = "";
					# take action only if we have a custom field that is a key to our spreadsheet
					if(!array_key_exists($aField['key'], $aRow)) continue;
					if($aField["type"] != "custom_field") continue;
					# get the key for the field and pass it through the row to get our value
					$value = $aRow[$aField['key']];
					# skip to next option if value is empty
					if(!$value){ $nCF_fail++; continue; }	
					if($bDebug) {var_dump($aField); echo "<br />";
						echo "<b>Found value: " . $value . "<br />";
						echo "Current value: " . get_post_meta($new_id, $aField['key'], true)."<br />";
					}
					# try and update the field value
					if(update_post_meta($new_id, $aField['key'], $value)) $nCF_success++;
					else $nCF_fail++;
					if($bDebug) echo "New value: " . get_post_meta($new_id, $aField['key'], true) . "</b><br /><br />";							 
				} # end foreach: column options
				if($nCF_success > 0) echo "<p class='cptdir-success'>Successfully updated {$nCF_success} custom fields.</p>";
				if($nCF_fail > 0) echo "<p class='cptdir-fail'>No change was made for {$nCF_fail} custom fields.</p>";
				
				/***
				* Custom Taxonomy Terms
				***/

				# Non-Heirarchical
				$this->import_terms($new_id, "ttax", $aRow, $bDebug );
				# Heirarchical
				$this->import_terms($new_id, "ctax", $aRow, $bDebug );

			} # end foreach: posts
		} # endif: import rows exist
		cptdir_fail("We couldn't find any rows to import.");
	} # end: run_import()
	
	private function import_terms($post_id, $type, $aRow, $bDebug = false){
		if($type == "ttax"){
			$label = $this->ttax->labels["name"];
			$tax = $this->ttax->name;
		}
		elseif($type == "ctax"){
			$label = $this->ctax->labels["name"];
			$tax = $this->ctax->name;
		}
		else return;
		echo "<b>Attempting to Import Custom Taxonomy Terms for <span style='color: midnightblue;'>'" . $label . "'</span>...</b><br />";
		if($bDebug) echo "<h4 class='debug'>Taxonomy Data:</h4>";
		# Loop through all column options
		foreach( $this->column_options as $aField ){
			$value = "";
			$aTerms = array();
			$aTermIds = array();
			$new_slug = "";
			$tax_insert = "";
				
			# take action only if we have a custom taxonomy field that is a key to our spreadsheet
			if(!array_key_exists($aField['key'], $aRow)) continue;		
			if($aField['type'] != $type) continue;
			# get the key for the field and pass it through the row to get our value
			$value = $aRow[$aField['key']];
			# skip to next option if value is empty
			if(!$value) continue;
			if($bDebug){ 
				var_dump($aField); echo "<br /><br />";
				echo "<b>Found terms: " . $aRow[$aField['key']] . "</b><br /><br />";
			}
			# get array of individual terms by separating at commas
			$aTerms = explode(",", $aRow[$aField['key']]);
			# Clean up whitespace
			foreach($aTerms as $k => $v){ $aTerms[$k] = preg_replace("/\s+/", " ", trim($v)); }
			if($bDebug){ echo "Individual Term Array: <br />"; var_dump($aTerms); echo "<br /><br />";}
			# Loop through terms and apply to post if not already in place
			foreach($aTerms as $term){
				if("" == $term) continue;
				if($bDebug) echo "<b>Term: $term</b><br />";
				# get the term ID if it exists
				if($term_id = term_exists($term, $tax)){
					if($bDebug){ echo "Term Exists:<br />"; var_dump($term_id); echo "<br /><br />"; }
					$aTermIds[] = intval($term_id['term_id']);
				} # endif: term exists
				# if term doesn't exist, insert it
				else{
					if( $bDebug) echo "Term doesn't exist.<br />";
					# create new term
					$new_slug = CPTDirectory::clean_str_for_url($term);
					$term_id = wp_insert_term( $term, $tax, array("slug" => $new_slug) );
					if($bDebug){ echo "Created term:<br />"; var_dump($term_id); echo "<br /><br />"; }
					$aTermIds[] = intval($term_id["term_id"]);
				}
			} # end foreach: found terms
			# Insert terms if we have a non-empty array of term IDs
			if(array() != $aTermIds) $tax_insert = wp_set_post_terms( $post_id, $aTermIds, $tax);
			if(is_array($tax_insert)) echo "<p class='cptdir-success'>Successfully set " . count($tax_insert) . " terms.</p>";
			else{ echo "<p>There was a problem inserting these terms.</p>"; }					
		} # end foreach: column options		
	} # end: import_terms()
} # end class
?>