<?php
class CPTD{

	static $classes = array('cptd-options', 'cptd-pt', 'cptd-tax', 'cptd-view', 'cptd-search-widget');

	static $pt; # post type object
	static $ctax; # category-like taxonomy object
	static $ttax; # tag-like taxonomy object
	
	/*
	* Main routine
	*/
	
	public static function setup(){
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
		
		## set empty options where necessary to avoid array key issues
		foreach(CPTD_Options::$settings as $setting){
			if(!isset(CPTD_Options::$options[$setting['name']])) {
				CPTD_Options::$options[$setting['name']] = '';
			}
		}
	} # end: setup()
	
	public static function setup_pt(){
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
	} # end: setup_pt()
	
	public static function setup_ctax(){
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
	} # end: setup_ctax()
	
	public static function setup_ttax(){
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
	} # end: setup_ttax()
	
	/* 
	* Admin Routines
	*/
	public static function admin_enqueue(){
		$screen = get_current_screen();
		
		## all cptdir pages
		$screens = array(
			'settings' => 'toplevel_page_cptdir-settings-page',
			'instructions' => 'cpt-directory_page_cptdir-instructions',
			'fields' => 'cpt-directory_page_cptdir-fields',
			'cleanup' => 'cpt-directory_page_cptdir-cleanup',
			'import' => 'cpt-directory_page_cptdir-import',
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
	public static function admin_menu() {
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
	public static function enqueue(){
		# CSS
		wp_enqueue_style("cptdir-css", cptdir_url("css/cptdir.css"));
		if(self::is_cptdir()){
			wp_enqueue_script('cptdir-lightbox-js', cptdir_url('/assets/lightbox/lightbox.min.js'), array('jquery'));
			wp_enqueue_style('cptdir-lightbox-css', cptdir_url('/assets/lightbox/lightbox.css'));
		}
	}
	# default field view (can be called by theme if needed from inside cptdir_custom_single)
	public static function default_fields($content = "", $type = "single", $callback = ""){
		global $post;
		$view = new CPTD_view(array("ID" => $post->ID, "type"=>$type));
		$view->do_fields($callback);
		return $content;
	}	
	# post type archive page
	public static function pt_archive(){
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
	public static function single_template($single_template){
		$pt = cptdir_get_pt();
		# do nothing if we're not viewing a single listing of our PT
		if(!is_singular($pt->name)) return $single_template;
	
		# add the_content filter for post content
		add_filter("the_content", array('CPTD',"do_single"));
		return $single_template;
	}
	# the_content filter for single listing
	public static function do_single($content){
		if(!in_the_loop()) return;
		# if theme has custom content function, do that and return
		## note that custom function has option to return $content
		if(function_exists("cptdir_custom_single")){ return cptdir_custom_single($content); }

		# otherwise set up default view
		return self::default_fields($content);
	}
	
	# Taxonomy term archives
	## Set templates for taxonomy archives
	public static function taxonomy_template($page_template){
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
	public static function taxonomy_content($content){
		# if theme has custom content function, do that and return
		## note that custom function has option to return $content
		if(function_exists("cptdir_custom_taxonomy_content")){ return cptdir_custom_taxonomy_content($content); }
	
		# otherwise set up default view
		global $post;
		$tax = cptdir_get_cat_tax() ? cptdir_get_cat_tax() : (cptdir_get_tag_tax() ? cptdir_get_tag_tax() : "");
		if(!is_object($tax)) return $content;
	
		return self::default_fields($content, "multi");
	}
	public static function page_templates( $page_template ){
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
	public static function front_page($content){
		$html = self::terms_html();
		return $content.$html;
	}
	
	# shortcode for cptd-terms
	public static function terms_html($atts = array()){
		# if we're not passed a taxonomy
		if(!$atts['taxonomy']){
			# get CPTD_tax object based on plugin settings
			$front_page_shows = isset(CPTD_Options::$options['front_page_shows'])
				? CPTD_Options::$options['front_page_shows'] 
				: '';
			$tax = ($front_page_shows == 'ctax') 
				? (
					CPTD::$ctax ? CPTD::$ctax : ''
				)
				: (
					$front_page_shows == 'ttax'
					? (
						CPTD::$ttax ? CPTD::$ttax : ''
					) : ''
				);
			if(!$tax) return;
			# get wp object
			$tax = $tax->obj;
		}
		# if we are passed a taxonomy
		else{
			# try to get by label
			if(!($tax = get_taxonomies(array('label' => $atts['taxonomy']), 'objects'))){			
				# then by name
				$tax = get_taxonomy($atts['taxonomy']);
			}
			if(is_array($tax)) $tax = array_pop($tax);
		}
		if(!$tax) return;
		# parse shortcode attributes
		$atts = shortcode_atts(
			array(
				'taxonomy' => $tax->name,
				'show_count' => isset(CPTD_Options::$options['tax_show_count_yes']) ? 'true' : 'false',
				'show_empty' => isset(CPTD_Options::$options['tax_show_empty_yes']) ? 'true' : 'false',
				'show_title' => isset(CPTD_Options::$options['tax_show_title_yes']) ? 'true' : 'false',
			), $atts, 'cptd-terms'
		);
		
		# arguments for get_terms
		$q_args = array();
		if($atts['show_empty'] == 'true') $q_args['hide_empty'] = false;
		$terms = get_terms($tax->name, $q_args);
		
		# check if we're being overridden by theme
		if(function_exists('cptdir_custom_terms_list')) return cptdir_custom_terms_list($terms);
		if(!$terms) return;

		# otherwise, generate HTML for list
		$html = '<div id="cptdir-terms-list">';
			if($atts['show_title'] == 'true') $html .= '<h2>'. $tax->labels->name .'</h2>';
			$html .= '<ul>';
			foreach($terms as $term){
				$html .= '<li>';
					$html .= '<a class="cptdir-term-link" href="'. get_term_link($term) .'">';
						$html .= $term->name;
					$html .= '</a>';
					if($atts['show_count'] == 'true'){
						$html .= ' ('.$term->count.')';
					}
				$html .= '</li>';
			}
			$html .= '</ul>';
		$html .= '</div>';
		return $html;		
	} # end: terms_html()
	
	# shortcode for A-Z listing
	public static function az_html(){
		# make sure we have a post type defined
		$pt = cptdir_get_pt();
		if(!$pt) return;
		
		# get the posts
		$posts = get_posts(array(
			'posts_per_page' => -1,
			'orderby' => 'post_title',
			'order' => 'ASC',
			'post_type' => $pt->name
		));
		if(!$posts) return;
		
		# generate the HTML string
		$html .= '<div id="cptdir-az-listing">';
			$html .= '<ul>';
			foreach($posts as $post){
				$html .= '<li><a href="'.get_permalink($post->ID).'">'.$post->post_title.'</a></li>';
			} # end foreach: posts
			$html .= '</ul>';
		$html .= '</div>';
		
		# return the HTML string
		return $html;
	} # end: az_html();
	# shortcode for search widget
	public static function search_widget($atts = array()){
		$widget = array(
			'title' => 'My Title'
		);
		return 'short code';
	} # end: search_widget()
	
	## Search Results Page
	public static function search_results($content){ 
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

	/**
	 * Helper Functions
	 */
	
	# require a file, checking first if it exists
	public static function req_file($path){ if(file_exists($path)) require_once $path; }
	
	# Sanitize form input
	public static function san($in){
		return trim(preg_replace("/\s+/", " ", strip_tags($in)));
	} # end: san()

	# check if we're viewing a CPTD-powered page (must be called after 'wp' hook)
	public static function is_cptdir(){
		$pt = CPTD::$pt;

		return ((is_singular($pt->name) || is_post_type_archive($pt->name)))
			|| ($tax = CPTD::$ctax && is_archive($tax->name))
			|| ($tax = CPTD::$ttax && is_archive($tax->name));		
	} # end: is_cptdir()

	# return a permalink-friendly version of a string
	public static function clean_str_for_url( $sIn ){
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
	} # end: clean_str_for_url()

	# same as above, but use underscore as default separator
	public static function clean_str_for_field($sIn){
		if( $sIn == "" ) return "";
		$sOut = trim( strtolower( $sIn ) );
		$sOut = preg_replace( "/\s\s+/" , " " , $sOut );					
		$sOut = preg_replace( "/[^a-zA-Z0-9 -_]/" , "",$sOut );	
		$sOut = preg_replace( "/--+/" , "-",$sOut );
		$sOut = preg_replace( "/__+/" , "_",$sOut );
		$sOut = preg_replace( "/ +- +/" , "-",$sOut );
		$sOut = preg_replace( "/ +_ +/" , "_",$sOut );
		$sOut = preg_replace( "/\s\s+/" , " " , $sOut );	
		$sOut = preg_replace( "/\s/" , "_" , $sOut );
		$sOut = preg_replace( "/--+/" , "-" , $sOut );
		$sOut = preg_replace( "/__+/" , "_" , $sOut );
		$nWord_length = strlen( $sOut );
		if( $sOut[ $nWord_length - 1 ] == "-" || $sOut[ $nWord_length - 1 ] == "_" ) { $sOut = substr( $sOut , 0 , $nWord_length - 1 ); } 
		return $sOut;		
	} # end: clean_str_for_field()

	public static function convert_space_to_nbsp( $sIn ){
		return preg_replace( '/\s/' , '&nbsp;' , $sIn );	
	} # end: convert_space_to_nbsp()

	# Generate a label, value, etc. for any given setting 
	## input can be a string or array and a full, formatted array will be returned
	## If $field is a string we assume the string is the label
	## if $field is an array we assume that at least a label exists
	## optionally, the parent field's name can be passed for better labelling
	public static function get_field_array( $field, $parent_name = ''){
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
	} # end: get_field_array()

	# Get array of choices for a setting field
	## This allows choices to be set as strings or arrays with detailed properties, 
	## so that either way our options display function will have the data it needs
	public static function get_choice_array($setting){
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
	} # end: get_choice_array()

	# get an array of IDs for all post type objects (default is published only, passing false returns all)
	public static function get_all_cpt_ids($bPub = true){
		# get all post objects
		$aPosts = self::get_all_cpt_posts($bPub);
		$aIDs = array();
		if($aPosts) foreach($aPosts as $post){
			$aIDs[] = $post->ID;
		}
		return $aIDs;
	} # end: get_all_cpt_ids()

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
	} # end: get_all_cpt_posts()

	# filter out WP extraneous post meta
	public static function filter_post_meta($a){
		global $view;
		$out = array();
		if(!$a) return;
		foreach($a as $k => $v){
			# if value is an array, take the first item
			if(is_array($v)){
				if(isset($v[0])) $v = $v[0];
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
	} # end: filter_post_meta()

	# Return list of all fields for single listing
	public static function get_fields_for_listing($id){
		$fields = self::filter_post_meta(get_post_meta($id));
		return $fields;
	} # end: get_fields_for_listing()

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
	} # end: get_all_custom_fields()

	# Check if a field is an ACF field
	public static function is_acf($field){
		if(is_string($field))
			return preg_match("/field_[\dA-z]+/", $field);
		elseif(is_array($field) && array_key_exists('name', $field))
			return preg_match("/field_[\dA-z]+/", $field['name']);
		return false;
	} # end: is_acf()

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
	} # end: get_acf_fields()

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
	} # end: get_meta_values()
} #end class
# require dependencies
foreach(CPTD::$classes as $class){
	CPTD::req_file(cptdir_dir("lib/class-{$class}.php"));
}
?>