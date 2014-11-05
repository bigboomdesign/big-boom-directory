<?php
class CPTDirectory{
# Sanitize form input
public static function san($in){
	return trim(preg_replace("/\s+/", " ", strip_tags($in)));
}
# Return slug-formatted string for given input
public static function clean_str_for_url( $sIn ){
	/*****
	Uncomment Lines In Between Commands To Troubleshoot at each step.
	*****/
	if( $sIn != "" && is_string( $sIn ) ) { 
		// Lowercase and Initial Whitespace Trim	
		$sOut = trim( strtolower( $sIn ) );
		$sOut = preg_replace( "/\s\s+/" , " " , $sOut );					
		//echo "<u>Lowercase and Initial Whitespace Trim:</u><br />'". convert_space_to_nbsp( $sOut ) ."'<br /><br />";

		// Alpha-Numeric, Spaces, and Dashes Only
		$sOut = preg_replace( "/[^a-zA-Z0-9 -]/" , "",$sOut );
		//echo "<u>Alpha-Numeric, Spaces, and Dashes Only:</u><br />'". convert_space_to_nbsp( $sOut ) ."'<br /><br />";

		// No Multiple Dashes
		$sOut = preg_replace( "/--+/" , "-",$sOut );
		//echo "<u>No Multiple Dashes:</u><br />'". convert_space_to_nbsp( $sOut ) ."'<br /><br />";

		// No Spaces Around Dashes
		$sOut = preg_replace( "/ +- +/" , "-",$sOut );
		//echo "<u>No Spaces Around Dashes:</u><br />'". convert_space_to_nbsp( $sOut ) ."'<br /><br />";	

		//Remove any Double Spaces
		$sOut = preg_replace( "/\s\s+/" , " " , $sOut );
		//echo "<u>Remove Double Spaces:</u><br />'". convert_space_to_nbsp( $sOut ) ."'<br /><br />";

		// 	Replace Remaining Spaces With Dash
		$sOut = preg_replace( "/\s/" , "-" , $sOut );
		//echo "<u>Replace Remaining Spaces With Dash:</u><br />'". convert_space_to_nbsp( $sOut ) ."'<br /><br />";

		// 	One Last Remove Multiple Dashes
		$sOut = preg_replace( "/--+/" , "-" , $sOut );
		//echo "<u>One Last Remove Multiple Dashes:</u><br />" . "'{$sDirty}'<br /><br />";		

		// Remove trailing dash
		$nWord_length = strlen( $sOut );
		if( $sOut[ $nWord_length - 1 ] == "-" ) { $sOut = substr( $sOut , 0 , $nWord_length - 1 ); } 
		return $sOut;
	}
	else{ return false;}
}	
# Return field_formatted string for given input
public static function str_to_field_name( $sIn  ){
	/*****
	Uncomment echo statements to see results at each step.
	*****/
	if( $sIn != "" && is_string( $sIn ) ) { 
		// Lowercase and Initial Whitespace Trim	
		$sOut = trim( strtolower( $sIn ) );
		$sOut = preg_replace( "/\s\s+/" , " " , $sOut );					
		//echo "<u>Lowercase and Initial Whitespace Trim:</u><br />'". convert_space_to_nbsp( $sOut ) ."'<br /><br />";

		// Alpha-Numeric, Spaces, and Underscores Only
		$sOut = preg_replace( "/[^a-zA-Z0-9 _]/" , "_",$sOut );
		//echo "<u>Alpha-Numeric, Spaces, and Underscores Only:</u><br />'". convert_space_to_nbsp( $sOut ) ."'<br /><br />";

		// No Multiple Underscores
		$sOut = preg_replace( "/__+/" , "_",$sOut );
		//echo "<u>No Multiple Underscores:</u><br />'". convert_space_to_nbsp( $sOut ) ."'<br /><br />";

		// No Spaces Around Underscores
		$sOut = preg_replace( "/ +_ +/" , "_",$sOut );
		//echo "<u>No Spaces Around Underscores:</u><br />'". convert_space_to_nbsp( $sOut ) ."'<br /><br />";	

		// Remove any Double Spaces
		$sOut = preg_replace( "/\s\s+/" , " " , $sOut );
		//echo "<u>Remove Double Spaces:</u><br />'". convert_space_to_nbsp( $sOut ) ."'<br /><br />";

		// Replace Remaining Spaces With Underscore
		$sOut = preg_replace( "/\s/" , "_" , $sOut );
		//echo "<u>Replace Remaining Spaces With Underscore:</u><br />'". convert_space_to_nbsp( $sOut ) ."'<br /><br />";

		// One Last Remove Multiple Underscores
		$sOut = preg_replace( "/__+/" , "_" , $sOut );
		//echo "<u>One Last Remove Multiple Underscores:</u><br />'" . convert_space_to_nbsp( $sOut ) . "'<br /><br />";		

		// Remove trailing Underscore
		$nWord_length = strlen( $sOut );
		if( $sOut[ $nWord_length - 1 ] == "_" ) { $sOut = substr( $sOut , 0 , $nWord_length - 1 ); } 
		return $sOut;
	}
	else{ return false;}
}
function convert_space_to_nbsp( $sIn ){
	return preg_replace( '/\s/' , '&nbsp;' , $sIn );	
}
public static function get_all_cpt_posts($bPub = true){
	$aOut = array();
	$pt = cptdir_get_pt();
	$slug = $pt->name;
	$args=array(
	  'post_type' => $slug,
	  'posts_per_page' => -1,
	);
	if($bPub) $args["post_status"] = "publish";
	$cpt_query = new WP_Query($args);
	if( $cpt_query->have_posts() ) {
	  while ($cpt_query->have_posts()) : $cpt_query->the_post();
	  	$aOut[] = $cpt_query->post;
	  endwhile;
	}
	wp_reset_query();
	return $aOut;
}
# filter out WP extraneous post meta
function filter_post_meta($a){
	global $view;
	$out = array();
	foreach($a as $k => $v){
		# if value is an array, take the first item
		if(is_array($v)){
			if($v[0]) $v = $v[0];
		}
		# do nothing if value is empty
		if("" == $v) continue;
		# check if this is an ACF field
		$bACF = self::is_acf($v);
		if($bACF){
			$view->acf_fields[$k] = $v;
		}
		# Filter out any fields that start with an underscore or that are empty
		## save any ACF fields
		if(
			!in_array($v, $out) 
				&& ( (strpos($k, "_") !== 0 || strpos($k, "_") === false)
					&& !$bACF
				)
		){
			#echo "$k: $v"; echo "<br /><br />";
			$out[$k] = $v;
		}
	}
	return $out;
}
# Return list of all fields for single listing
public static function get_fields_for_listing($id){
	$fields = self::filter_post_meta(get_post_meta($id));
	return $fields;
}
# Return list of all fields for custom post type
public static function get_all_custom_fields($bActive = false){
	# array of custom fields we'll return
	$aCF = array();
	
	# Go through posts and scour field names
	do{
		# Quit if we don't have a post type to work with
		if(!($obj = cptdir_get_pt())) break;
		# Quit if we don't have a slug
		if(!is_object($obj) || !property_exists($obj, "name")) break;
		$slug = $obj->name;
		if(!$slug) break;
		# Get ID's of posts for our CPT
		global $wpdb;
		$aPosts = $wpdb->get_results( "SELECT DISTINCT ID FROM " . $wpdb->prefix . "posts WHERE post_type='$slug'" );
		# Loop through posts ID's for CPT and get list of custom fields
		if(!$aPosts) break;
		foreach($aPosts as $post){
			# Grab all custom fields for post
			$aPM = get_post_custom_keys($post->ID);
			if(!$aPM) continue;

			foreach($aPM as $field){
				# Filter any fields that have already been found and ones that start with the underscore character
				# If a field passes this filter, we'll add it to the $aCF array and show it in the table
				if(!in_array($field, $aCF) && (strpos($field, "_") !== 0 || strpos($field, "_") === false)){
					# This will check if the field has any posts that actually use it
					if($bActive){
						if((!in_array($field, $aCF)) && ("" != get_post_meta($post->ID, $field, true))) $aCF[] = $field;
					}
					else{
						if(!in_array($field, $aCF)) $aCF[] = $field; 
					}
				}
			}
		}
	} while(0); #end: scour posts for field values
	
	# Get Advanced Custom Fields fields if we're not filtering out inactive fields
	if(!$bActive){
		$aACF_fields = self::get_acf_fields();
		foreach($aACF_fields as $a){ if(!in_array($a['name'], $aCF)) $aCF[] = $a['name']; }
	}
	return $aCF;
}
# Check if a field is an ACF field
public static function is_acf($field){
	return preg_match("/field_[\dA-z]+/", $field);
}
# Get advanced custom fields
public static function get_acf_fields(){
	global $wpdb;
	$out = array();
	$args=array(
	  'post_type' => "acf",
	  'post_status' => 'publish',
	  'posts_per_page' => -1,
	);
	$acf_query = new WP_Query($args);
	if( $acf_query->have_posts() ) {
	  while ($acf_query->have_posts()) : $acf_query->the_post();
	  	$post = $acf_query->post;
	  	$r = $wpdb->get_results("SELECT meta_value FROM ". $wpdb->prefix . "postmeta WHERE post_id = " . $post->ID . " AND meta_key LIKE \"%field_%\"");
	  	foreach($r as $field){
	  		$aField = unserialize($field->meta_value);
	  		$out[] = $aField;
	  	}
	  endwhile;
	}
	wp_reset_query();
	return $out;
}
# Get all meta values for a certain key
public static function get_meta_values( $key = '', $type = "", $status = 'publish' ) {
    if( empty( $key ) ) return;
	$pt = cptdir_get_pt();
	if(!$type){
		if(!$pt) return;
		$type = $pt->name;
	}
    global $wpdb;
    $r = $wpdb->get_col( $wpdb->prepare( "
        SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm
        LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE pm.meta_key = '%s' 
        AND p.post_status = '%s' 
        AND p.post_type = '%s'
    ", $key, $status, $type ) );

    return $r;
}
# Display Public Settings Page
public static function do_settings_page(){
?>
<div class="wrap">
<h2 class="cptdir-header">CPT Directory: Settings</h2>
<form method="post" action="options.php">
    <?php settings_fields( 'cptdir-settings-group' ); ?>
    <?php do_settings_sections( 'cptdir-settings-group' ); ?>
    <p class="cptdir-success">Setting all 3 fields for the Custom Post Type is required for any other features to become active.</p>
    <hr class="cptdir-hr" />
    <h3 class="cptdir-header">Custom Post Type</h3>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">Singluar Label</th>
        <td><input type="text" name="cpt_sing" value="<?php echo get_option('cpt_sing'); ?>" /></td>
        </tr>
       <tr valign="top">
        <th scope="row">Plural Label</th>
        <td><input type="text" name="cpt_pl" value="<?php echo get_option('cpt_pl'); ?>" /></td>
        </tr> 
       <tr valign="top">
        <th scope="row">SEO Friendly Slug (e.g. /custom-post-type)</th>
        <td>
        	<input type="text" name="cpt_slug" value="<?php echo get_option('cpt_slug'); ?>" />
        	<p class="description">Make sure to Save your Permalink Settings after changing this value</p>
        </td>
        </tr>              
    </table>
    <hr class="cptdir-hr"/>
    <h3 class="cptdir-header">Custom Taxonomies</h3>
    <h4 class="title cptdir-header">Heirarchical (similar to category)</h4>
    <table class="form-table indent">
        <tr valign="top">
        <th scope="row">Singular Label</th>
        <td><input type="text" name="cpt_ctax_sing" value="<?php echo get_option('cpt_ctax_sing'); ?>" /></td>
        </tr>
        <th scope="row">Plural Label</th>
        <td><input type="text" name="cpt_ctax_pl" value="<?php echo get_option('cpt_ctax_pl'); ?>" /></td>
        </tr> 
        <th scope="row">SEO Friendly Slug (e.g. /custom-taxonomy)</th>
        <td>
        	<input type="text" name="cpt_ctax_slug" value="<?php echo get_option('cpt_ctax_slug'); ?>" />
           	<p class="description">Make sure to Save your Permalink Settings after changing this value</p>
        </td>
        </tr>                
    </table>
    <h4 class="title cptdir-header">Non-Heirarchical (similar to tag)</h4>
    <table class="form-table indent">
        <tr valign="top">
        <th scope="row">Singular Label</th>
        <td><input type="text" name="cpt_ttax_sing" value="<?php echo get_option('cpt_ttax_sing'); ?>" /></td>
        </tr>
        <th scope="row">Plural Label</th>
        <td><input type="text" name="cpt_ttax_pl" value="<?php echo get_option('cpt_ttax_pl'); ?>" /></td>
        </tr> 
        <th scope="row">SEO Friendly Slug (e.g. /custom-taxonomy)</th>
        <td>
        	<input type="text" name="cpt_ttax_slug" value="<?php echo get_option('cpt_ttax_slug'); ?>" />
        	<p class="description">Make sure to Save your Permalink Settings after changing this value</p>
        </td>
        </tr> 
    </table>
    <hr class="cptdir-hr"/>
    <h3 class="cptdir-header">Search Results</h3>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">Page</th>
        <?php
        # Dropdown page list
        $args = array(
        	"selected" => get_option("cpt_search_page"), 
        	"name" => "cpt_search_page",
        	"show_option_none" => "Select page for search results"
        );
        ?>
        <td><?php wp_dropdown_pages($args); ?></td>
        </tr>
    </table>    
    
	<?php submit_button(); ?>

</form>
</div>
<?php
}
# Custom Fields Settings Page
public static function do_fields_page(){
?>
	<div class="wrap">
    <h2 class="cptdir-header">CPT Directory: Custom Fields</h2>
    <?php 
    # Check if we have any custom fields to show 
    do{
		$aCF = self::get_all_custom_fields();
		$aActiveFields = self::get_all_custom_fields(true);
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
						<a data-field="<?php echo $field; ?>" id="cptdir-remove-<?php echo $field; ?>" class="cptdir-remove-field">Remove Field</a>
						<div id="cptdir-remove-<?php echo $field; ?>-message"></div>
					<?php }
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
}

###
# Front end views
###

# Search results
public static function do_search_results(){
	# Make an array of filters from post
	$aFilters = array();
	# Loop through post and sanitize data to store in array
	foreach($_POST as $k => $v){
		# don't include blank fields
		if(!$v) continue;	
		# make sure we only use our own search keys
		$key = sanitize_key($k);
		$matches = array();
		# check first for dropdown options
		if(!preg_match("/cptdir-(.*)?-select/", $key, $matches)) 
			# then check if we're dealing with the widget ID we passed
			if( $k != "cptdir-search-widget-id") continue;
		# if we have matched a dropdown select, store the trimmed-down key and sanitized value
		if(array() != $matches)
			$aFilters[$matches[1]] = sanitize_text_field($v);
		# otherwise see if we have a widget ID and store it for later
		elseif(preg_match("/cptdir_search_widget-(\d)+?/", $v, $matches))
			$widget_id = $matches[1];
	}
	# Make a WP_Query object from the search filters
	$pt = cptdir_get_pt();
	$args = array(
		"post_type" => $pt->name,
		"posts_per_page" => -1,
	);
	# Add taxonomy query if necessary
	$args['tax_query'] = array();	
	# Check if we have the category tax set
	if(array_key_exists("ctax", $aFilters)){
		$ctax = cptdir_get_cat_tax();
		$ctax_args = array(
			"taxonomy" => $ctax->name,
			"terms" => intval($aFilters["ctax"]),
		);
		# push this array into main tax_query array
		$args['tax_query'][] = $ctax_args;
	}
	# Check if we have the tag tax set
	if(array_key_exists("ttax", $aFilters)){
		$ttax = cptdir_get_tag_tax();
		$ttax_args = array(
			"taxonomy" => $ttax->name,
			"terms" => intval($aFilters["ttax"]),
		);
		# push this array into main tax_query array
		$args['tax_query'][] = $ttax_args;	
	}
	# Check for custom fields
	$args["meta_query"] = array();
	foreach($aFilters as $k => $v){
		if($k == "ctax" || $k == "ttax") continue;
		$meta_args = array(
			"key" => $k,
			"value" => $v,
		);
		$args["meta_query"][] = $meta_args;
	}
	# Make the QP_Query object and loop through results
	$s_query = new WP_Query($args);
	# If you wish to make your own search results layout, 
	# create a function named cptdir_search_results()
	# you will be passed the wp_query object and an array containing the post type and taxonomies
	if(function_exists("cptdir_search_results")){ cptdir_search_results($s_query, $widget_id); return; }

	# Copying the block of code below may be a good start.	
	### BEGIN SEARCH RESULTS ###
	if($s_query->have_posts()){
	?>
		<p class="cptdir-post-count">We found <?php echo $s_query->post_count . " " . $pt->labels['name'] ;?> that matched your query.</p>
		<div id="cptdir-search-results">
	<?php
		# Search Results Loop
		do{
			$s_query->the_post();
			$post = $s_query->post;
	?>
			<div class="cptdir-search-result-item">
			<?php
				if(has_post_thumbnail()) the_post_thumbnail("thumbnail", array("class" => "cptdir-archive-thumb alignleft"));
			?>
				<h3 class="cptdir-archive-header"><a href="<?php echo get_permalink($post->ID); ?>"><?php echo $post->post_title; ?></a></h3>			
				<div style="clear: left;"></div>
			</div><?php # .cptdir-search-result-item ?>
		<?php
		} while($s_query->have_posts());
		# end: search results loop
		?>
		</div><?php # #cptdir-search-results ?>
		<?php
	}
	# endif: have_posts
	else{
	?>
		<p>Sorry, we didn't find any results. Please narrow your search parameters</p>
	<?php
		# Display the widget that originally sent us here.
		$widget_options = get_option("widget_cptdir_search_widget");
		if($widget_options){
			if($widget_options[$widget_id]) the_widget("CPTD_search_widget", $widget_options[$widget_id]);
		}
	}
	### END SEARCH RESULTS ###
}
} #end class
?>