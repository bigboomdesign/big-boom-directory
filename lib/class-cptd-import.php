<?php

class CPTD_Import{

	// turn on for debugging
	var $bDebug = true;
	
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

	#function __construct($pt, $ctax, $ttax){
	function __construct(){
		#$this->post_type = $pt;
		#$this->ctax = $ctax;
		#$this->ttax = $ttax;

		if( isset( $_POST['cptd-import-post-type'] ) ) $this->post_type = sanitize_text_field( $_POST['cptd-import-post-type'] );

		$this->bFile = array_key_exists("cptd-import-file", $_FILES) && !empty($_FILES["cptd-import-file"]);
		$this->bForm = array_key_exists("cptd-import-submit", $_POST);
		
		# Get options for csv columns
		$custom_options = $this->get_custom_column_options();
		$this->column_options = array_merge($this->post_fields, $custom_options);
		
		# Add new custom fields to be created to column options if the form is posted
		if($this->bForm){
			# match all array keys in post with string "new-cf"
			$new_keys = preg_grep("/cptd-import-new-cf-/", array_keys($_POST));
			# take out extraneous stuff from all keys to get the field_name
			if($new_keys){
				$new_keys = preg_replace("/cptd-import-new-cf-(.*)/", "$1", $new_keys);
				# loop through found keys and add type/label for column_options array
				$new_fields = array();
				foreach($new_keys as $key){ 
					# if custom field already exists, update the type so it can automap
					foreach($this->column_options as $i => $aOption){ 
						if($aOption['key'] == $key){
							$this->column_options[$i]['type'] = 'new_custom_field';
							continue 2; 
						}
					}
					$new_fields[] = array("key" => $key, "label" => $key, "type" => "new_custom_field" ); 
				}
				# merge with existing column options
				$this->column_options = array_merge($this->column_options, $new_fields);
			}
		}
		
		# If post is set, create the user-generated column map
		if($this->bFile && $this->bForm) $this->create_column_map();
	}
	public function do_import_page(){
	?>
	<div class="wrap">
		<h2 class="cptd-header">CPT Directory: Import</h2>
		<form method="post" enctype="multipart/form-data" id="cptd-file-select">
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Import File (must be .csv)</th>
					<td><input type="file" name="cptd-import-file" id="cptd-import-file">
					<br/><p style="display: none;" id="cptd-file-message" class="cptd-fail"></p>
					</td>
				</tr> 
				<tr>
					<th scope="row">Post Type</th>
					<td>
						<?php
						# select post type
						$post_types = get_post_types();
						if( $post_types ) { ?>
							<select id="cptd-import-post-type" name="cptd-import-post-type">
							<?php
							foreach($post_types as $pt){

								# skip over post types used internally
								if( 'cptd_pt' == $pt || 'cptd_tax' == $pt ) continue;
							?>
								<option value="<?php echo $pt; ?>" ><?php echo $pt; ?></option>
							<?php
							}
							?>
							</select>
						<?php
						} # end if: post types exist
						?>
					</td>
				</tr>
			</table>
			<?php submit_button("Submit File", "primary", "cptd-file-submit"); ?>
		</form>
		<div id="cptd-import-content">
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
			if(!$this->headers){ echo cptd_fail("No rows found."); break; }
			echo cptd_success($this->num_rows." rows found", "h4", "cptd-header");
			echo "<hr class='cptd-hr' />";
			if(array() !== $this->headers){
			?>
				<form id="cptd-import-form" name="cptd-import-form" method="post" enctype="multipart/form-data">				
					<h3>How will you determine the title of your imported posts?</h3>
					<label for="title_use_column" ><input type="radio" value="column" id="title_use_column" name='title_method' /> I'll use a column from my .csv file</label><br />
					<label for="title_use_merge_tag"><input type="radio" value="merge_tag" id="title_use_merge_tag" name='title_method' /> I'll define a pattern involving multiple columns</label>
					<div id="title_define_merge_tag" style="display: none;" >
						<label for="title_pattern"><b>Pattern:</b><br />
							<input type="text" name='title_pattern' id="title_pattern"/>
						</label>
						<p class='description'>Use the format <code>{column_name}</code> to specify a column value in the post title</p>
						<p class='description'><b>Make sure that you assign each column below that you use in your pattern.</b></p>
					</div>
					<hr />
					<h3>We found the following column headers in your spreadsheet</h3>
					<p>Please indicate how the data should be stored for each column.</p>
					<?php 
					foreach($this->headers as $header){
					?>
						<div class="cptd-found-header">
							<h5 class="cptd-header"><?php echo $header; ?></h5>
							<?php $this->do_fields_dropdown( CPTD_Helper::clean_str_for_field( $header ) ); ?>
						</div>
						<hr class="cptd-hr" />
					<?php
					} # end foreach: headers
					submit_button("Import", "primary", "cptd-import-submit");
					
					# hidden field to store the post type
				?>
					<input type="hidden" name="cptd-import-post-type" value="<?php echo $this->post_type; ?>" />
				</form>
				<?php
			}
		} while(0); # endif: file is posted
		# Check if file and form are both posted
		elseif($this->bFile && $this->bForm){
			$this->run_import();
		} # end: file and form are posted
	}
	# Load headers, rows, and row count into object	
	private function parse_csv(){
		if(!$this->bFile) return false;
		$file = $_FILES["cptd-import-file"];
		# open file
		if(!($fh = fopen($file["tmp_name"], "r"))){ echo cptd_fail("Couldn't read file."); return false;}
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
					$my_row[ CPTD_Helper::clean_str_for_field( $header ) ] = $row[ $n ];
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
		
		$column_options = array();

		# get all custom fields keys
		$custom_fields = CPTD_Helper::get_all_field_keys();

		# add custom fields to column options
		foreach( $custom_fields as $field ){ $column_options[] = array( "key" => $field, "label" => "Custom Field: $field", "type"=>"custom_field"); }

		# add taxonomies to the column options
		if( $taxonomy_ids = CPTD::$taxonomy_ids ) {
			
			foreach( $taxonomy_ids as $tax_id ) {

				$tax = new CPTD_Tax( $tax_id );

				$column_options[] = array( 'key' => $tax->handle, 'label' => 'Taxonomy: '. $tax->handle, 'type' => 'taxonomy' );
			}
		}

		#if( $this->ttax ) $aFields[] = array("key"=> $this->ttax->name, "label" => "Taxonomy: " . $this->ttax->name, "type"=>'ttax');
		#if($this->ctax) $aFields[] = array("key"=> $this->ctax->name, "label" => "Taxonomy: " . $this->ctax->name, "type"=>'ctax');	
		
		return $column_options;
	}
	# Create map from column_options field to csv_fields based on POST input
	private function create_column_map(){
		foreach( $_POST as $k => $v){

			# sanitize POST content
			$key = sanitize_text_field( $k );
			$val = sanitize_text_field( $v );

			# we're going to be matching only fields starting with cptd-import-
			$matches = array();
			# these need to be cleared out each time
			$csv_field = "";
			$import_field = "";
			$bValid = false;
			if(preg_match("/cptd-import-(.*)/", $key, $matches)){
				$csv_field = $matches[1];
				if("submit" == $csv_field) continue;
				$import_field = $val;
				# Check if this is a valid choice
				foreach($this->column_options as $aOption){
					# new custom fields can go in automatically and get mapped to themselves
					## note bValid is not set to true here since we're forcing the value and not using the POST key and value
					if($aOption['type'] == "new_custom_field")  $this->column_map[$aOption['key']] = $aOption['key'];
					if($import_field == $aOption['key']){ $bValid = true; continue; }
				}
				if($bValid){ 
					$this->column_map[$import_field] = $csv_field;
				}
				else $this->debug($csv_field, "Field not assigned: ", 4);
			}
		} # end foreach: $_POST
		# for any column options that weren't used, map them to an empty string
		foreach($this->column_options as $k => $aOption){
			if(!array_key_exists($aOption["key"], $this->column_map)) $this->column_map[$aOption["key"]] = "";
		}
		$this->debug($this->column_options, "Column Options: ");
		$this->debug($this->column_options, "Column Map: ");
	}
	# Display dropdown, given fields and slug for the <select > name/ID
	public function do_fields_dropdown($slug){
		if(array() == $this->column_options) return false;
		?>
		<select autocomplete="off" id="cptd-import-<?php echo $slug; ?>" name="cptd-import-<?php echo $slug; ?>" class="cptd-import-select" data-field="">
			<option value="">Select existing field</option>
		<?php
			foreach($this->column_options as $field){
			?>
				<option value="<?php echo $field["key"]; ?>" class="cptd-import-option-<?php echo $slug ?>"><?php echo $field["label"]; ?></option>
			<?php
			}
		?>
		</select>
		<div class="cptd-cf-or">OR</div>
		<div class="cptd-new-cf">
			<input type="checkbox" name="cptd-import-new-cf-<?php echo $slug; ?>" id="cptd-import-new-cf-<?php echo $slug; ?>" /> Create new custom field: 
			&nbsp;<b> <?php echo $slug; ?></b>
		</div>
		<?php
	}
	# Run the main import process
	private function run_import(){
		if(!$this->bFile || !$this->bForm) return false;
		$this->debug( $_POST, "POST:");
		$this->debug($_FILES, "FILES:");
		$this->parse_csv();		
		# if( $nImport_rows > 0 ) {
		if( $this->num_rows > 0) {
			echo "<h3 class='cptd-header'>Attempting to import " . $this->num_rows . " rows...</h3>";

			/**********
			Grab existing permalinks from Entries With Given Post Type, and then all Taxonomy Terms with given Custom Taxonomy
			Used to avoid creating duplicate entries.	
			**********/

			// Collect Existing Permalinks
			$permalinks = array();

			global $wpdb;
			$post_name_query = "SELECT DISTINCT post_name FROM " . $wpdb->posts .
				" WHERE post_type = '". $this->post_type . "'";

			$post_name_results = $wpdb->get_results( $post_name_query );

			foreach( $post_name_results as $r ) {

				$permalinks[] = $r->post_name;
			}

			$this->debug($permalinks, "Existing Posts: ");

			// Collect Existing Taxonomy Terms
			if($this->ctax) $aCtax_terms = get_terms($this->ctax->name);
			if($this->ttax) $aTtax_terms = get_terms($this->ttax->name);
			$this->debug($aCtax_terms, "Heirarchical Terms: ");
			$this->debug($aTtax_terms, "Non-Heirarchical Terms: ");

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
				if(!$aRow["post_title"] && $_POST['title_method'] == 'column'){ echo cptd_fail("No title was found."); $nPost_fail++; continue; }
				# put together title if we're using merge tags
				elseif($_POST['title_method'] == 'merge_tag'){
					$post_title = sanitize_text_field($_POST['title_pattern']);
					$post_title = self::replace_merge_tags($post_title, $aRow);
					$aRow['post_title'] = $post_title;
				}
				if(!$aRow['post_title']){
					echo cptd_fail("Couldn't generate title from the specified pattern."); 
					$nPost_fail++; 
					continue;
				}
								
				# Set slug based on title if it doesn't already exist
				if(!$aRow["post_name"]) $aRow["post_name"] = CPTD_Helper::clean_str_for_url($aRow["post_title"]);
								
				echo '<hr />';
				echo '<h4 class="cptd-header">Importing Post: <span style="color: midnightblue;">' . $aRow['post_title'] . '</b></h4>';
				
				$this->debug($csvRow, "CSV Row Data: ");
				$this->debug($aRow, "Import Row Data: " );
				
				# Check if permalink is free for the taking
				echo "<p>Formatted Title: <b>'" . $aRow["post_name"] . "'</b></p>";				
				if( !in_array( $aRow["post_name"] , $permalinks ) ) { echo "<p class='cptd-success'>Permalink is available</p>"; }
				# If permalink exists, get the post object we will be updating
				else{ 
					$bPost_exists = true;
					echo "<p><span class='cptd-fail'>Already Exists</span>: Attempting to update post data</p>"; 
					foreach($aPosts as $post){ if($post->post_name == $aRow["post_name"]) $oPost = $post;  }
					$this->debug($oPost, "Post to Update: ");
				}
								
				
				/***
				* Post Object Data
				***/

				$post_args = array();
				$post_args = array(
					'post_title'     => $aRow["post_title"],
					'post_status'    => 'publish', 
					'post_type'      => $_POST['cptd-import-post-type'],
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
					 echo cptd_success( ($bPost_exists?"Updated":"Created") . " post with ID " . $new_id);
				}
				# Continue to next row if we failed
				else{ echo cptd_fail("There was a problem with this post."); $nPost_fail++; continue; }
				
				/***
				* Custom Fields
				***/

				echo "<b>Attempting to Importing Custom Field Information...</b><br />";
				# Loop through all column options
				foreach( $this->column_options as $aField  ){
					$value = "";
					# take action only if we have a custom field that is a key to our spreadsheet
					if(!array_key_exists($aField['key'], $aRow)) continue;
					if($aField["type"] != "custom_field" && $aField["type"] != "new_custom_field") continue;
					# get the key for the field and pass it through the row to get our value
					$value = $aRow[$aField['key']];
					# skip to next option if value is empty
					if(!$value){ $nCF_fail++; continue; }	
						$this->debug($aField, "Field: ");
						$this->debug($value, "Found Value: ");
						$this->debug(get_post_meta($new_id, $aField['key'], true), "Current value: ");
					# try and update the field value
					if(update_post_meta($new_id, $aField['key'], $value)) $nCF_success++;
					else $nCF_fail++;
					if($this->bDebug) echo "New value: " . get_post_meta($new_id, $aField['key'], true) . "</b><br /><br />";							 
				} # end foreach: column options
				if($nCF_success > 0) echo "<p class='cptd-success'>Successfully updated {$nCF_success} custom fields.</p>";
				if($nCF_fail > 0) echo "<p class='cptd-fail'>No change was made for {$nCF_fail} custom fields.</p>";
				
				/***
				* Custom Taxonomy Terms
				***/

				# Non-Heirarchical
				if($this->ttax) $this->import_terms($new_id, "ttax", $aRow);
				# Heirarchical
				if($this->ctax) $this->import_terms($new_id, "ctax", $aRow);

			} # end foreach: posts
		} # endif: import rows exist
		else echo cptd_fail("We couldn't find any rows to import.");
	} # end: run_import()
	
	private function import_terms($post_id, $type, $aRow){
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
		if($this->bDebug) echo "<h4 class='debug'>Taxonomy Data:</h4>";
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
			if($this->bDebug){ 
				var_dump($aField); echo "<br /><br />";
				echo "<b>Found terms: " . $aRow[$aField['key']] . "</b><br /><br />";
			}
			# get array of individual terms by separating at commas
			$aTerms = explode(",", $aRow[$aField['key']]);
			# Clean up whitespace
			foreach($aTerms as $k => $v){ $aTerms[$k] = preg_replace("/\s+/", " ", trim($v)); }
			if($this->bDebug){ echo "Individual Term Array: <br />"; var_dump($aTerms); echo "<br /><br />";}
			# Loop through terms and apply to post if not already in place
			foreach($aTerms as $term){
				if("" == $term) continue;
				if($this->bDebug) echo "<b>Term: $term</b><br />";
				# get the term ID if it exists
				if($term_id = term_exists($term, $tax)){
					if($this->bDebug){ echo "Term Exists:<br />"; var_dump($term_id); echo "<br /><br />"; }
					$aTermIds[] = intval($term_id['term_id']);
				} # endif: term exists
				# if term doesn't exist, insert it
				else{
					if( $this->bDebug) echo "Term doesn't exist.<br />";
					# create new term
					$new_slug = CPTD::clean_str_for_url($term);
					$term_id = wp_insert_term( $term, $tax, array("slug" => $new_slug) );
					if($this->bDebug){ echo "Created term:<br />"; var_dump($term_id); echo "<br /><br />"; }
					$aTermIds[] = intval($term_id["term_id"]);
				}
			} # end foreach: found terms
			# Insert terms if we have a non-empty array of term IDs
			if(array() != $aTermIds) $tax_insert = wp_set_post_terms( $post_id, $aTermIds, $tax);
			if(is_array($tax_insert)) echo "<p class='cptd-success'>Successfully set " . count($tax_insert) . " terms.</p>";
			else{ echo "<p>There was a problem inserting these terms.</p>"; }					
		} # end foreach: column options		
	} # end: import_terms()
	/*
	* Helper Functions 
	*/
	
	# replace merge tags with field values for a given string and post ID
	public static function replace_merge_tags($s, $row){
		# get any merge tags present
		$matches = array();
		if(preg_match_all("/{([^}]*)?}/", $s, $matches)){
			# loop through matches if anything was found
			foreach($matches[1] as $n => $match){
				$field = trim($match);

				# the string we'll use to replace the merge tag
				$replacement = $row[$field] ? $row[$field] : '';
				
				# replace merge tag with the proper replacement
				$s = str_replace($matches[0][$n], $replacement, $s);
			} # end foreach: matches
		} # endif: merge tags exist
		
		$s = trim($s);
	
		return $s;
	} # end: replace_merge_tags()
	
	# dump a variable in debug mode, passing a message and header size
	private function debug($var, $msg, $size = 3){
		if($this->bDebug){ echo "<h{$size} class='debug'>$msg</h{$size}>"; var_dump($var); echo "<br /><br />"; }
	}
} # end class
?>