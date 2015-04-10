<?php
class CPTD{

	static $classes = array('cptd-options', 'cptd-pt', 'cptd-tax', 'cptd-view', 'cptd-search-widget');

	static $pt; # post type object
	static $ctax; # category-like taxonomy object
	static $ttax; # tag-like taxonomy object
	
	/*
	* Main routine
	*/
	
	function setup(){
		if(!self::setup_pt()) return;

		self::$pt->register_pt();

		# Create custom heirarchical taxonomy
		if($ctax = self::setup_ctax())
			$ctax->register_tax();
		
		# Create custom non-heirarchical taxonomy
		if($ttax = self::setup_ttax())
			$ttax->register_tax();
			
		# add options that depend on post type
		## front_page
		CPTD_Options::$settings['front_page'] = array(
			'name' => 'front_page',
			'type' => 'dropdown_pages',
			'label' => 'Directory Front Page',
			'show_option_none' => 'Select page for directory home'
		);
		## front_page_shows
		CPTD_Options::$settings['front_page_shows'] = array(
			'name' => 'front_page_shows', 'type' => 'select',
			'label' => 'Front Page Shows',
			'choices' => array(
				array(
					'value' => '',
					'label' => 'None'
				)
			)
		);
		if($ctax){
			CPTD_Options::$settings['front_page_shows']['choices'][] = array(
				'value' => 'ctax',
				'label' => self::$ctax->pl			
			);
		}
		if($ttax){
			CPTD_Options::$settings['front_page_shows']['choices'][] = array(
				'value' => 'ttax',
				'label' => self::$ttax->pl			
			);		
		}
		## tax_show_empty
		CPTD_Options::$settings[] = array(
			'name' => 'tax_show_empty',
			'label' => 'Show empty terms',
			'type' => 'checkbox',
			'choices' => 'Yes'
		);
		## tax_show_count
		CPTD_Options::$settings[] = array(
			'name' => 'tax_show_count',
			'label' => 'Show post count',
			'type' => 'checkbox',
			'choices' => 'Yes'
		);
		CPTD_Options::$settings[] = array(
			'name' => 'tax_show_title',
			'label' => 'Show title for terms list',
			'type' => 'checkbox',
			'choices' => 'Yes'
		);
	}
	function setup_pt(){
		if(self::$pt) return self::$pt;
		if(
			!($sing = CPTD_Options::$options["cpt_sing"])
			 || !($pl = CPTD_Options::$options['cpt_pl'])
			 || !($slug = CPTD_Options::$options['cpt_slug'])
			 || !class_exists("CPTD_pt")
		) return false;
		$obj = new CPTD_pt($slug, $sing, $pl);
		self::$pt = $obj;
		return $obj;
	}
	function setup_ctax(){
		if(self::$ctax){ return self::$ctax; }
		if(
			!($sing = CPTD_Options::$options['ctax_sing'])
			  || !($pl = CPTD_Options::$options['ctax_pl'])
			  || !($slug = CPTD_Options::$options['ctax_slug'])
			  || !($pt = cptdir_get_pt())
		) { return false;}
		$obj = new CPTD_tax($slug, $sing, $pl, $pt->name, true );
		self::$ctax = $obj;
		return $obj;		
	}
	function setup_ttax(){
		if(self::$ttax) return self::$ttax;
		if(
			!($sing = CPTD_Options::$options['ttax_sing'])
			|| !($pl = CPTD_Options::$options['ttax_pl'])
			|| !($slug = CPTD_Options::$options['ttax_slug'])
			|| !($pt = cptdir_get_pt())
		) return false;
		$obj = new CPTD_tax($slug, $sing, $pl, $pt->name, false);
		self::$ttax = $obj;
		return $obj;
	}
	
	/* 
	* Admin Routines
	*/
	function admin_enqueue(){
		$screen = get_current_screen();
		
		## all cptdir pages
		$screens = array(
			'settings' => 'toplevel_page_cptdir-settings-page',
			'instructions' => 'cpt-directory_page_cptdir-instructions',
			'fields' => 'cpt-directory_page_cptdir-fields',
			'cleanup' => 'cpt-directory_page_cptdir-cleanup',
		);		
		if(in_array($screen->id, $screens)){
			wp_enqueue_style("cptdir-admin-css", cptdir_url("css/cptdir-admin.css"));		
		}
		## fields page
		if($screen->id == $screens['fields']){
			wp_enqueue_script('cptdir-fields-js', cptdir_url('js/cptdir-fields.js'), array('jquery'));
		}
		## cleanup page
		elseif($screen->id == $screens['cleanup']){
			wp_enqueue_script('cptdir-cleanup-js', cptdir_url('js/cptdir-cleanup.js'), array('jquery'));
		}
	}
	function admin_menu() {
		add_menu_page('CPT Directory Settings', 'CPT Directory', 'administrator', 'cptdir-settings-page', array('CPTD_Options', 'settings_page'));
		add_submenu_page( 'cptdir-settings-page', 'CPT Directory Settings', 'Settings', 'administrator', 'cptdir-settings-page', array('CPTD_Options', 'settings_page'));
		add_submenu_page( 'cptdir-settings-page', 'CPT Directory Instructions', 'Instructions', 'administrator', 'cptdir-instructions', array('CPTD_Options', 'instructions_page'));
		add_submenu_page( 'cptdir-settings-page', 'Edit Fields | CPT Directory', 'Fields', 'administrator', 'cptdir-fields', array('CPTD_Options','fields_page' ));
		add_submenu_page( 'cptdir-settings-page', 'Clean Up | CPT Directory', 'Clean Up', 'administrator', 'cptdir-cleanup', array('CPTD_Options','cleanup_page'));	
		add_submenu_page("cptdir-settings-page", "Import | CPT Directory", "Import", "administrator", "cptdir-import", array('CPTD_Options','import_page'));
	}
	/*
	* Front End Routines
	*/
	function enqueue(){
		# CSS
		wp_enqueue_style("cptdir-css", cptdir_url("css/cptdir.css"));
	}
	# default field view (can be called by theme if needed from inside cptdir_custom_single)
	function default_fields($content = "", $type = "single", $callback = ""){
		global $post;
		$view = new CPTD_view(array("ID" => $post->ID, "type"=>$type));
		$view->do_fields($callback);
		return $content;
	}	
	# post type archive page
	function pt_archive(){
		$pt = cptdir_get_pt();
		if(!$pt) return;
		if(!is_post_type_archive($pt->name)) return;
		if(function_exists('cptdir_custom_archive')){
			add_filter('the_content', 'cptdir_custom_archive');
			return;
		}
		add_filter('the_content', array('CPTD', 'do_single'));
	}
	# single template
	function single_template($single_template){
		$pt = cptdir_get_pt();
		# do nothing if we're not viewing a single listing of our PT
		if(!is_singular($pt->name)) return $single_template;
	
		# add the_content filter for post content
		add_filter("the_content", array('CPTD',"do_single"));
		return $single_template;
	}
	# the_content filter for single listing
	function do_single($content){
		# if theme has custom content function, do that and return
		## note that custom function has option to return $content
		if(function_exists("cptdir_custom_single")){ return cptdir_custom_single($content); }

		# otherwise set up default view
		return self::default_fields($content);
	}
	
	# Taxonomy term archives
	## Set templates for taxonomy archives
	function taxonomy_template($page_template){
		# do nothing if we're not viewing a taxonomy archive
		if(!is_tax()) return $page_template;
	
		# get custom taxonomy objects and return if we're not viewing either of their archive pages
		$ctax = cptdir_get_cat_tax();
		$ttax = cptdir_get_tag_tax();
		# get taxonomy name
		if(
			!(
				($bCtax = ($ctax && is_tax($ctax->name)))
					|| ($bTtax = ($ttax && is_tax($ttax->name)))
			)
		)
		return $page_template;
		$taxname = $bCtax ? $ctax->name : ($bTtax ? $ttax->name : "");
		if(!$taxname) return $page_template;

		# the_content for taxonomy archive post content
		add_filter("the_content", array('CPTD', "taxonomy_content"));
		return $page_template;
	}	
	## this function fires on the_content() for each post in the loop on taxonomy pages, when no template is present in the theme
	function taxonomy_content($content){
		# if theme has custom content function, do that and return
		## note that custom function has option to return $content
		if(function_exists("cptdir_custom_taxonomy_content")){ return cptdir_custom_taxonomy_content($content); }
	
		# otherwise set up default view
		global $post;
		$tax = cptdir_get_cat_tax() ? cptdir_get_cat_tax() : (cptdir_get_tag_tax() ? cptdir_get_tag_tax() : "");
		if(!is_object($tax)) return $content;
	
		return self::default_fields($content, "multi");
	}
	function page_templates( $page_template ){
		# directory home
		$home_id = CPTD_Options::$options['front_page'];
		if($home_id && is_page($home_id)){
			add_filter('the_content', array('CPTD', 'front_page'));
			return $page_template;
		}
		# search results
		$search_id = CPTD_Options::$options["search_page"];
		if ( $search_id && is_page( $search_id ) ) {
			# Do search results when the_content() is called
			add_filter("the_content", array('CPTD', 'search_results'));
			return $page_template;
		}
		return $page_template;
	}
	## Directory home
	function front_page($content){
		$html = self::terms_html();
		return $content.$html;
	}
	function terms_html($atts = array()){
		# if no attributes are passed in, we'll do whatever is set for the directory home page
		if(!$atts){
			# what should be shown here (ctax or ttax)?
			$show = CPTD_Options::$options['front_page_shows'];
			if(!$show) return;
		
			# get the taxonomy whose terms we'll show
			if($show == 'ctax') $tax = CPTD::$ctax;
			elseif($show == 'ttax') $tax = CPTD::$ttax;
			if(!$tax) return;
		
			# grab the terms for the chosen taxonomy
			$args = array();
			if(isset(CPTD_Options::$options['tax_show_empty_yes']))
				$args['hide_empty'] = false;
			if(!($terms = get_terms($tax->name, $args))) return;
		
			# check if we're being replaced by custom content
			if(function_exists('cptdir_custom_front_page')) return cptdir_custom_front_page($terms);
		}
		# if attributes are defined, grab terms based on user input
		else{
			$atts = shortcode_atts(
				array(
					'taxonomy' => '',
					'show_count' => false,
					'show_empty' => false,
					'show_title' => false
				), $atts, 'cptd-terms'
			);
			# try to get by label
			if(!($tax = get_taxonomies(array('label' => $atts['taxonomy']), 'objects'))){			
				# then by name
				$tax = get_taxonomy($atts['taxonomy']);
			}
			if(!$tax) return;
			if($atts['show_empty'] == 'true') $atts['hide_empty'] = false;
			if(!($terms = get_terms($tax->name, $atts))) return;
			
			# get CPTD tax object
			if($tax->name == CPTD::$ctax->name) $tax = CPTD::$ctax;
			elseif($tax->name == CPTD::$ttax->name) $tax = CPTD::$ttax;
		}
		# generate HTML for list
		$html = '<div id="cptdir-terms-list">';
			if(
				isset(CPTD_Options::$options['tax_show_title_yes'])
					|| $atts['show_title'] == 'true'
			) $html .= '<h2>'. $tax->pl .'</h2>';
			foreach($terms as $term){
				$html .= '<li>';
					$html .= '<a class="cptdir-term-link" href="'. get_term_link($term) .'">';
						$html .= $term->name;
					$html .= '</a>';
					if(
						CPTD_Options::$options['tax_show_count_yes']
						|| $atts['show_count'] == 'true'
					){
						$html .= ' ('.$term->count.')';
					}
				$html .= '</li>';
			}
		$html .= '</div>';
		return $html;		
	}
	## Search Results Page
	function search_results($content){ 
		self::do_search_results();
		return $content;
	}
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
	} #	end: do_search_results()

	/*
	* Helper Functions
	*/
	
	# require a file, checking first if it exists
	static function req_file($path){ if(file_exists($path)) require_once $path; }
	
	# Sanitize form input
	public static function san($in){
		return trim(preg_replace("/\s+/", " ", strip_tags($in)));
	}
	# return a permalink-friendly version of a string
	function clean_str_for_url( $sIn ){
		if( $sIn == "" ) return "";
		$sOut = trim( strtolower( $sIn ) );
		$sOut = preg_replace( "/\s\s+/" , " " , $sOut );					
		$sOut = preg_replace( "/[^a-zA-Z0-9 -]/" , "",$sOut );	
		$sOut = preg_replace( "/--+/" , "-",$sOut );
		$sOut = preg_replace( "/ +- +/" , "-",$sOut );
		$sOut = preg_replace( "/\s\s+/" , " " , $sOut );	
		$sOut = preg_replace( "/\s/" , "-" , $sOut );
		$sOut = preg_replace( "/--+/" , "-" , $sOut );
		$nWord_length = strlen( $sOut );
		if( $sOut[ $nWord_length - 1 ] == "-" ) { $sOut = substr( $sOut , 0 , $nWord_length - 1 ); } 
		return $sOut;
	}
	# same as above, but use underscore as default separator
	function clean_str_for_field($sIn){
		if( $sIn == "" ) return "";
		$sOut = trim( strtolower( $sIn ) );
		$sOut = preg_replace( "/\s\s+/" , " " , $sOut );					
		$sOut = preg_replace( "/[^a-zA-Z0-9 -_]/" , "",$sOut );	
		$sOut = preg_replace( "/--+/" , "-",$sOut );
		$sOut = preg_replace( "/__+/" , "_",$sOut );
		$sOut = preg_replace( "/ +- +/" , "-",$sOut );
		$sOut = preg_replace( "/ +_ +/" , "_",$sOut );
		$sOut = preg_replace( "/\s\s+/" , " " , $sOut );	
		$sOut = preg_replace( "/\s/" , "-" , $sOut );
		$sOut = preg_replace( "/--+/" , "-" , $sOut );
		$sOut = preg_replace( "/__+/" , "_" , $sOut );
		$nWord_length = strlen( $sOut );
		if( $sOut[ $nWord_length - 1 ] == "-" || $sOut[ $nWord_length - 1 ] == "_" ) { $sOut = substr( $sOut , 0 , $nWord_length - 1 ); } 
		return $sOut;		
	}
	function convert_space_to_nbsp( $sIn ){
		return preg_replace( '/\s/' , '&nbsp;' , $sIn );	
	}
	# Generate a label, value, etc. for any given setting 
	## input can be a string or array and a full, formatted array will be returned
	## If $field is a string we assume the string is the label
	## if $field is an array we assume that at least a label exists
	## optionally, the parent field's name can be passed for better labelling
	function get_field_array( $field, $parent_name = ''){
		$id = $parent_name ? $parent_name.'_' : '';
		if(!is_array($field)){
			$id .= self::clean_str_for_field($field);
			$out = array();
			$out['type'] = 'text';
			$out['label'] = $field;
			$out['value'] = $id;
			$out['id'] .= $id;
			$out['name'] = $id;
		}
		else{
			# do nothing if we don't have a label
			if(!array_key_exists('label', $field)) return $field;
			
			$id .= array_key_exists('name', $field) ? $field['name'] : self::clean_str_for_field($field['label']);
			$out = $field;
			if(!array_key_exists('id', $out)) $out['id'] = $id;
			if(!array_key_exists('name', $out)) $out['name'] = $id;
			# make sure all choices are arrays
			if(array_key_exists('choices', $field)){
				$out['choices'] = self::get_choice_array($field);
			}
		}
		return $out;
	}
	# Get array of choices for a setting field
	## This allows choices to be set as strings or arrays with detailed properties, 
	## so that either way our options display function will have the data it needs
	function get_choice_array($setting){
		extract($setting);
		if(!isset($choices)) return;
		$out = array();
		if(!is_array($choices)){
			$out[] = array(
				'id' => $name.'_'.self::clean_str_for_field($choices),
				'label' => $choices, 
				'value' => self::clean_str_for_field($choices)
			);
		}
		else{
			foreach($choices as $choice){
				if(!is_array($choice)){
					$out[] = array(
						'label' => $choice,
						'id' => $name . '_' . self::clean_str_for_field($choice),
						'value' => self::clean_str_for_field($choice)
					);
				}
				else{
					# if choice is already an array, we need to check for missing data
					if(!array_key_exists('id', $choice)) $choice['id'] = $name.'_'.self::clean_str_for_field($choice['label']);
					if(!array_key_exists('value', $choice)) $choice['value'] = $name.'_'.self::clean_str_for_field($choice['label']);
					## if this choice has children, do a few extra things
					if(array_key_exists('children', $choice)){
						# add a class to indicate this class has children
						$choice['class'] .= ' has-children';
						# loop through child fields and make sure we have full arrays for them all
						foreach($choice['children'] as $k => $child_choice){
							$child_choice = self::get_field_array($child_choice);
							$choice['children'][$k] = $child_choice;
						}
					}
					$out[] = $choice;
				}
			}
		}
		return $out;
	}	
	# get an array of IDs for all post type objects (default is published only, passing false returns all)
	public static function get_all_cpt_ids($bPub = true){
		# get all post objects
		$aPosts = self::get_all_cpt_posts($bPub);
		$aIDs = array();
		if($aPosts) foreach($aPosts as $post){
			$aIDs[] = $post->ID;
		}
		return $aIDs;
	}
	# get an array of post objects for our PT (default is published only, passing false returns all)
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
		if(!$a) return;
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
		$r = $wpdb->get_col( 
			$wpdb->prepare(
				"SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm
				LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				WHERE pm.meta_key = '%s' 
				AND p.post_status = '%s' 
				AND p.post_type = '%s'", 
				$key, $status, $type 
			)
		);
		return $r;
	}
} #end class
# require dependencies
foreach(CPTD::$classes as $class){
	CPTD::req_file(cptdir_dir("lib/class-{$class}.php"));
}
?>