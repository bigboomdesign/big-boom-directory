<?php
/** 
 * Models a directory field to be displayed on the front end
 *
 * @since 	2.0.0
 */

class BBD_Field {

	/**
	 * The field key (e.g. `email` or `field_abc123`)
	 *
	 * @param 	string
	 * @since 	2.0.0
	 */
	var $key = '';

	/**
	 * The text label for this field (e.g. `Email`)
	 *
	 * @since 	2.0.0
	 */
	var $label = '';

	/**
	 * The field type
	 *
	 * @param	string
	 * @since 	2.0.0
	 */ 
	var $type = '';

	/**
	 * The position of the field within its parent field set
	 *
	 * For ACF, this is `order_no` and for ACF Pro it's `menu_order`
	 *
	 * @param 	int
	 * @since 	2.2.1
	 */
	var $position = null;

	/**
	 * The value of this field, if any
	 *
	 * @param 	mixed
	 * @since 	2.0.0
	 */
	var $value = '';

	/**
	 * Whether this is an ACF field
	 *
	 * @param 	(bool|null)
	 * @since 	2.0.0
	 */
	var $is_acf = null;

	/**
	 * Whether this is a social media field (i.e. available for auto detection)
	 *
	 * @param 	bool
	 * @since 	2.0.0
	 */
	var $is_social_field = false;

	/**
	 * Whether this is a URL field (i.e. available for auto detection and link text)
	 *
	 * Possible cases where this is true:
	 * 		- field key is 'web', 'website', or 'url'
	 * 		- field key contains '_website' or '_url'
	 *
	 * @param 	bool
	 * @since 	2.0.0
	 */
	var $is_url_field = false;

	/**
	 * The ACF field array (if $this->is_acf)
	 *
	 * @param 	array
	 * @since 	2.0.0
	 */
	var $acf_field;

	/**
	 * An array of distinct field values for this field
	 *
	 * @param 	array
	 * @since 	2.0.0
	 */
	var $all_values = null;

	/**
	 * Class methods
	 *
	 * - __construct()
	 * - get_value()
	 * - get_html()
	 * - get_form_element_html()
	 *
	 * - get_acf_by_key()
	 *
	 * - is_acf()
	 * - load_acf_data()
	 * - get_all_values()
	 */

	/**
	 * Construct a new instance
	 *
	 * @param	(array) 	May be an ACF field or a field key string
	 * @since 	2.0.0
	 */
	public function __construct( $field ) {

		# if we're passed a string, we'll assume it's a field key
		if( is_string( $field ) ) {
			$this->key = $field;
		}

		# if we're being passed an array
		elseif( is_array( $field ) ) {

			# load the field key
			if( array_key_exists( 'key', $field ) ) $this->key = $field['key'];
		}

		if( ! $this->key ) return;

		# load the ACF field info if applicable
		$this->is_acf();

		# is this a social field?
		$social_fields = BBD_Helper::$auto_social_field_keys;
		foreach( $social_fields as $social_key ) {
			if( false === strpos( $this->key, $social_key ) ) continue;
			
			# if we have a match
			$this->is_social_field = true;

			# no need to check further
			break;
		}

		# is this a URL field?
		if(
			# first, make sure we don't usurp any social media field auto-detection
			! $this->is_social_field &&

			# then check that the field key matches a URL field (see doc abov for $this->is_url_field)
			( 
				'web' == $this->key || 
				'website' == $this->key ||
				'url' == $this->key ||
				false !== strpos( $this->key, '_website' ) ||
				false !== strpos( $this->key, '_url' )
			)
		) {
			$this->is_url_field = true;
		}

		# if not an ACF field, we'll try and load as much data as we can about the field
		if( ! $this->is_acf ) {
			$this->label = ucwords( str_replace('_', ' ', $this->key ) );
		}

	} # end: __construct()

	/**
	 * Get and the value of this field for a specific post, in end-usable format
	 *
	 * If no post is given, we'll use the current post in the loop
	 *
	 * For example of 'end-usable format', suppose we have an ACF field that has type 'image'. We will return the 
	 * image URL, no matter which return format is selected in the ACF settings.
	 *
	 * @param 	(int|string) 	$post_id 	The post ID we are displaying the field for (default: global $post)
	 * @since 	2.0.0
	 */
	function get_value( $post_id = 0 ) {

		$value = '';

		global $bbd_view;
		
		# make sure we have a post ID
		if( ! $post_id )  {
			global $post;
			$post_id = $post->ID;
		}
		if( empty( $post_id ) ) return '';

		# check if the value is set within the view already
		if( isset( $bbd_view->post_meta[ $post_id ][ $this->key ] ) ) {

			$value = $bbd_view->post_meta[ $post_id ][ $this->key ];
		}

		# if not, get the value from the DB
		else $value = get_post_meta( $post_id, $this->key, true );

		# if we don't have a value, we're going to offer up some hooks and punt
		if( empty( $value ) ) {

			# apply filters to the value so users can edit it
			$value = apply_filters( 'bbd_field_value', $value, $this, $post_id );
			$value = apply_filters( 'bbd_field_value_' . $this->key, $value, $this, $post_id );
			return $value;
		}

		/**
		 * Special field types
		 *
		 *		- Checkbox
		 * 		- Image
		 * 		- Date Picker
		 *		- Google Map
		 */

		if( 'checkbox' == $this->type ) {

			$value = $bbd_view->post_meta[ $post_id ][ $this->key ];
			$value = maybe_unserialize($value);
			
			# if we have an array, we'll return a comma-separated string of the array items
			if( is_array( $value ) ) {

				# loop through the values and store the ACF labels instead of the values
				foreach( $value as &$v ) {
					if( ! empty( $this->acf_field['choices'][ $v ] ) ) {
						$v = $this->acf_field['choices'][ $v ];
					}
				}

				$value = implode( ', ', $value );
			}

		} # end if: checkbox field
		 
		if( 'image' == $this->type ) {

			$src = '';

			# get the appropriate size, falling back on thumbnail for archive and medium for single
			$size = ( isset( $bbd_view->image_size ) ? 
				$bbd_view->image_size :
				( isset( $bbd_view->view_type ) && 'single' == $bbd_view->view_type  ? 
					'thumbnail' :
					'medium'
				)
			);


			/**
			 * ACF gives the option of multiple save formats for images (object/url/id)
			 *
			 * For the array key that specifies the format, ACF uses `save_format` 
			 * and ACF Pro uses `return_format`
			 */
			if( $this->is_acf ) {

				$format = '';

				if( ! empty( $this->acf_field['save_format'] ) ) {
					$format = $this->acf_field['save_format'];
				}
				elseif( ! empty( $this->acf_field['return_format'] ) ) {
					$format = $this->acf_field['return_format'];
				}

				switch( $format ){

					case 'object':
					case 'url':
						# if set to return an object, we'll have an array as the value
						# otherwise we'll have the URL string
						$src = is_array( $value ) ? $value['sizes'][ $size ] : '';
					break;

					case 'id';
						$src = wp_get_attachment_image_src( $value, $size);
						if( $src ) $src = $src[0];
					break;
				}

				# If we still don't have a source try the ID again in case object/url is ignored 
				# due to not using get_field
				if( ! $src && intval( $value ) > 0  ) {
					if( $src = wp_get_attachment_image_src( $value, $size) ) $src = $src[0];
				}

			} # end if: ACF field

			$value = $src;

		} # end if: image field

		/**
		 * Date picker field
		 */
		if( 'date_picker' == $this->type && $this->is_acf ) {

			# the format saved in ACF
			$format_in = '';

			# for ACF non-pro
			if( ! empty( $this->acf_field['date_format'] ) ) {
				$format_in = $this->acf_field['date_format'];
				$format_out = $this->acf_field['display_format'];

				# conversion from JS to PHP
				$format_convert = array(
					'yymmdd' => 'Ymd',
					'dd/mm/yy' => 'd/m/Y',
					'mm/dd/yy' => 'm/d/Y',
					'yy_mm_dd' => 'Y_m_d'
				);

				$format_in = $format_convert[ $format_in ];
				$format_out = $format_convert[ $format_out ];
			}

			# for ACF pro
			elseif( ! empty( $this->acf_field['return_format'] ) ) {

				# ACF pro went with a fixed save format
				$format_in = 'Ymd';

				# but the user can still specify the display format so we need to support this
				$format_out = $this->acf_field['display_format'];

				# note that the actual `return_format` is insignificant to us, since we are
				# grabbing the value straight from the DB
			}

			if( ! empty( $format_in ) ) {

				# create the PHP date/time object
				$date = DateTime::createFromFormat( $format_in, $value );

				# generate the value based on the ACF display type
				if( $date ) {
					$value = $date->format( $format_out );
				}
			}
		
		} # end: date picker field

		/**
		 * Google Map field
		 */
		if ( 'google_map' == $this->type && $this->is_acf ) {

			$value = maybe_unserialize($value);

		} # end: google map field

		/**
		 * oEmbed field
		 */
		if( 'oembed' == $this->type ) {
			$width = $this->acf_field['width'];
			$height = $this->acf_field['height'];
			$value = wp_oembed_get( $value, array( 'width' => $width, 'height' => $height ) );
		}

		/**
		 * Post-processing for the field value
		 */

		# apply a general filter to the value
		$value = apply_filters( 'bbd_field_value', $value, $this, $post_id );

		# apply a specific filter to the value, for this particular field key
		$value = apply_filters( 'bbd_field_value_' . $this->key, $value, $this, $post_id );

		return $value;
	
	} # end: get_value()


	/**
	 * Output the HTML for this field for a specific post
	 *
	 * @param 	(int|string) 	$post_id 	The post ID we are displaying the field for (default: global $post)
	 * @since 	2.0.0
	 */
	public function get_html( $post_id = 0 ) {

		global $bbd_view;
		if( empty( $bbd_view ) ) $bbd_view = new BBD_View();

		# get the field value
		$value = $this->get_value( $post_id );

		# apply filter to the label so users can edit it
		$label = array(
			'text' => $this->label,
			'before' => '<label>',
			'after' => ': &nbsp;</label>'
		);
		$label = apply_filters( 'bbd_field_label_' . $this->key, $label, $this );

		# apply filter to the field wrap so users can hook in and edit
		$field_wrap = array(
			'classes' 		=> array( 'bbd-field', $this->type, $this->key ),
			'id'			=> '',
			'before_tag' 	=> 'div',
			'after_tag' 	=> 'div',
		);
		$field_wrap = apply_filters( 'bbd_field_wrap_' . $this->key, $field_wrap, $this );

		/**
		 * Special cases
		 * 
		 * - auto detect social media fields
		 * - return if no value is in place
		 * - auto detect URL fields
		 * - images
		 * - gallery
		 * - google map
		 *
		 */

		/**
		 * Auto detect social media fields
		 */
		if( $bbd_view->auto_detect_social ) {

			# see if we have a social media field key
			if( $this->is_social_field ) {

				# if this is the first social media field, set the indicator and open up a wrapping div for the icons				
				if( 0 == count( $bbd_view->completed_social_fields ) ) {
					?>
					<div class='bbd-social-icons'>
					<?php
				}

				# display the field
				if( $value ) {

					# make sure we have a valid Font Awesome icon
					$fa_icon = $this->key;
					if( false !== strpos( $fa_icon, 'facebook' ) ) $fa_icon = 'facebook';
					elseif( false !== strpos( $fa_icon, 'twitter' ) ) $fa_icon = 'twitter';
					elseif( false !== strpos( $fa_icon, 'tube' ) ) $fa_icon = 'youtube';
					elseif( false !== strpos( $fa_icon, 'instagram' ) ) $fa_icon = 'instagram';
					elseif( false !== strpos( $fa_icon, 'pinterest' ) ) $fa_icon = 'pinterest';
					elseif( false !== strpos( $fa_icon, 'plus' ) ) $fa_icon = 'google-plus';
					elseif( false !== strpos( $fa_icon, 'linked' ) ) $fa_icon = 'linkedin';
				?>
					<a target="_blank" href="<?php echo $value; ?>"><i class="fa fa-<?php echo $fa_icon; ?>" ></i></a>
				<?php
				}

				# load this fields into the completed social fields array
				$bbd_view->completed_social_fields[] = $this->key;

				# check if this is the last social media field and close the wrapping div if so
				if( count( $bbd_view->completed_social_fields ) >= count( $bbd_view->social_fields_to_check ) ) {
					
					$bbd_view->completed_social_fields = array(); 
					?>
					</div><!-- .bbd-social-icons -->
					<?php
				}

				return;
				
			} # end if: field key is a social media field
		
		} # end if: auto detect social is enabled

		/** 
		 * At this point, we'll do nothing further if we don't have a value
		 * We needed to do stuff for social media detection even if values are empty
		 */
		if( empty( $value ) ) return '';

		/**
		 * Auto-detect URL fields
		 */
		if( $bbd_view->auto_detect_url ) {

			# see if we have a website field key
			if( $this->is_url_field ) {

				# get the link text
				if( empty( $bbd_view->post_type->url_link_texts[ $this->key ] ) ) $link_text = 'View Website';
				else $link_text = $bbd_view->post_type->url_link_texts[ $this->key ];

				# do our best to make sure we have a valid URL
				if( 'http' != substr( $value, 0, 4 ) ) $value = 'http://' . $value;
			?>
				<div class="bbd-field text <?php echo $this->key; ?>">
						<a target="_blank" class='bbd-website-link' href="<?php echo esc_url( $value ); ?>" >
							<?php echo apply_filters('bbd_link_text', $link_text, $this ); ?>
						</a>
				</div>
			<?php

			return;
			
			} # end if: website field key

		} # end if: auto detect website field


		/**
		 * Image field
		 */
		if( 'image' == $this->type && ! empty( $value ) ){
				
			# make the image link to the listing page if we are on an archive page or search results view
			$link = '';

			if( 'archive' == $bbd_view->view_type || 'search-results' == $bbd_view->view_type ) {

				global $post;
				$link = get_permalink( $post->ID );
			}

			# set the class for the image wrapper
			$wrapper_class = 'bbd-field image ' . $this->key;

			# add image alignment to the class
			if( 'left' == $bbd_view->image_alignment || 'right' == $bbd_view->image_alignment ) {
				$wrapper_class .= ' ' . $bbd_view->image_alignment;
			}
			?>
			<div class="<?php echo $wrapper_class; ?>" >
			<?php
				if( $link ) {
				?>
					<a href="<?php echo $link; ?>">
				<?php
				}
				?>
						<img src="<?php echo esc_url( $value ); ?>" />
				<?php
				if( $link ) {
				?>
					</a>
				<?php
				}
			?>
			</div>
			<?php

			# go to next field after showing the image
			return;

		} # endif: image field

		/** 
		 * Gallery field
		 */
		if( 'gallery' == $this->type ) {

			# unserialize and make sure we have images
			$image_ids = unserialize( $value );
			if( ! $image_ids  ) {
				return;
			}

			# query the DB for all attachment metadata (so we don't have to call a WP function multiple times
			# that will hit the database for every image)
			global $wpdb;

			$image_query = "SELECT post_id, meta_value FROM " . $wpdb->postmeta . 
				" WHERE meta_key = '_wp_attachment_metadata' " . 
				" AND post_id IN ( " . implode( ", ", $image_ids ) . " )";

			$image_results = $wpdb->get_results( $image_query );

			if( ! $image_results ) {
				return;
			}

			# get the uploads directory URL
			$uploads_dir = wp_upload_dir();
			$uploads_url = $uploads_dir['baseurl'];

			# display the gallery
			$image_num = 0;
			?>
			<div class="bbd-gallery <?php echo $this->key; ?>">
			<?php
				foreach( $image_results as $row ) {

					$image_num++;
					
					$image_data = unserialize( $row->meta_value );

					# make sure we have a thumbnail size
					if( ! isset( $image_data['sizes']['thumbnail']['file'] ) ) continue;

					/**
					 * Check whether we need a date path within the uploads folder.
					 *
					 * We can't rely on the current value of 'uploads_use_yearmonth_folders', since this
					 * may have changed since the images were uploaded.
					 *
					 * If the main image file starts with a date folder path, then we need to assume 
					 * that the thumbnail is in the same folder.  Unfortunately, we don't have data about this 
					 * tied to the thumbnail itself, so we have to pull the date path from the main image
					 */
					$date_string = '';
					$date_match = array();
					if( preg_match( "/\d\d\d\d\/\d\d\//", $image_data['file'], $date_match ) ) {
						$date_string = $date_match[0];
					}

					$thumbnail_file = $image_data['sizes']['thumbnail']['file'];
					$full_size_file = $image_data['file'];

					$thumbnail_url = $uploads_url . '/' . $date_string . $thumbnail_file;
					$full_size_url = $uploads_url . '/' . $full_size_file;
				?>
					<a 
						class="bbd-gallery-thumb <?php echo $image_num; ?>" 
						href="<?php echo $full_size_url; ?>" 
						data-lightbox="gallery-<?php echo $this->key; ?>"
					>
						<img class="bbd-image <?php echo $this->key . " " . $image_num; ?>" src="<?php echo $thumbnail_url; ?>" />
					</a>
				<?php
				} #end foreach: gallery image
			?>
			</div>
		<?php

			return;
		} # end if: gallery field

		/**
		 * Google Map field
		 */
		if( 'google_map' == $this->type ) {
			$height = ! empty( $this->acf_field['height'] ) ? $this->acf_field['height'] : 400;
			$zoom = ! empty( $this->acf_field['zoom'] ) ? $this->acf_field['zoom'] : 14;

		?>
			<div class="bbd-field google_map <?php echo $this->key; ?>" style="height:<?php echo $height; ?>px;"
				data-lat="<?php echo $value['lat']; ?>"
				data-lng="<?php echo $value['lng']; ?>"
				data-address="<?php echo $value['address']; ?>"
				data-zoom="<?php echo $zoom; ?>"
			></div>
		<?php

			wp_enqueue_script('bbd-gmaps', 'https://maps.googleapis.com/maps/api/js');
			wp_enqueue_script('bbd-gmap-field', bbd_url('/js/bbd-google-map-field.js'), array('jquery', 'bbd-gmaps'));

			return;

		} # end if: google map field

		/**
		 * end: special cases
		 *
		 * At this point, all fields not addressed by special cases will be displayed the same way
		 */

		# output the field HTML
		if( ! empty( $field_wrap['before_tag'] ) ) {

			# open the wrap element
		?>
			<<?php 
				echo $field_wrap['before_tag'] . ' ';
				if( ! empty( $field_wrap['classes'] ) ) echo 'class="' . implode(' ', $field_wrap['classes'] ) . '" '; 
				if( ! empty( $field_wrap['id'] ) ) echo 'id="' . $field_wrap['id'] .'" ';
			?> 
			>
		<?php 
		}
			echo $label['before'];
			echo $label['text'];
			echo $label['after'];
			echo $value;

		if( ! empty( $field_wrap['after_tag'] ) ) {
		?>
			</<?php echo $field_wrap['after_tag']; ?>>
		<?php
		} # end if: field wrap has a tag set

	} # end: get_html()

	/**
	 * Generate form element HTML for this field (e.g. <select ... >...</select>
	 *
	 * Uses the same settings API as the main plugin settings in order to account for the field types, 
	 * available choices, etc.
	 *
	 * @param 	array 	$setting 			The HTML element config
	 * @see 	lib/class-bbd-options.php 	BBD_Options::do_settings_field()
	 *
	 * @since 	2.0.0
	 */
	public function get_form_element_html( $setting = array() ) {

		# get choices for `select`, `checkbox`
		if( 'select' == $setting['type'] || 'checkbox' == $setting['type'] ) {

			# whether to allow empty values
			$allow_empty = ( 'checkbox' != $setting['type'] );

			# load choices for the field by getting all meta values
			$choices = $this->get_all_values( $allow_empty );
			foreach( $choices as $value ) {
				
				# we may have the empty setting as an array
				if( is_array( $value ) ) {
					$setting['choices'][] = $value; 
					continue;
				}

				# all other values should be strings
				$setting['choices'][] = array( 
					'id' => $setting['id'] . '_' . BBD_Helper::clean_str_for_field( $value ),
					'value' => $value,
					'label' => $value,
				);
			}
		}

		# load the auto-completed field array
		$setting = BBD_Helper::get_field_array( $setting );
	?>
		<label for='<?php echo $setting[ 'id' ]; ?>'><?php echo $this->label; ?><br />
			<?php BBD_Options::do_settings_field( $setting, 'bbd_search', $_POST ); ?>
		</label>
	<?php
	} # end: get_form_element_html()

	/**
	 * Attempts to load ACF field info from $this->key, usually when the key is a plain string and 
	 * we need more info about the field
	 */
	public function get_acf_by_key() {

		# make sure ACF is active
		if( ! function_exists( 'get_field_object' ) ) return;

		# make sure we have a field key set
		if( empty( $this->key ) ) return;

		/**
		 * We're using WPDB since we don't have a post ID for get_post_meta(). We are searching for a relational
		 * row in postmeta that ACF has generated, whose form is _field_key => field123abc
		 *
		 * Note that we are just grabbing the first match we find, assuming that multiple fields with the same
		 * key will have the same field type, etc when created in ACF.
		 */
		global $wpdb;

		$acf_relation_query = "SELECT meta_value FROM " . $wpdb->postmeta . 
			" WHERE meta_key='_" . $this->key . "' LIMIT 1";
		$r = $wpdb->get_results( $acf_relation_query );

		# make sure we have a valid result
		if( empty( $r ) || is_wp_error( $r ) ) return;

		# the ACF field ID (e.g. field123abc)
		$acf_field_id = $r[0]->meta_value;

		# overwrite the text key with the field ID that we found
		$this->key = $acf_field_id;

		/**
		 * With the new field key, re-load the ACF data for this field
		 * Note that the original plain key is restored via load_acf_data() once the ACF data is extracted
		 */
		$this->load_acf_data();

	} # end: get_acf_by_key()

	/**
	 * Sets and returns the `is_acf` class property, or return if already set.  
	 * Initializes $this->is_acf by calling $this->load_acf_data() if necessary
	 *
	 * @since 	2.0.0
	 */
	public function is_acf() {

		# make sure we have a key
		if( empty( $this->key ) ) return false;

		# return the value if already set
		if ( is_bool( $this->is_acf ) ) return $this->is_acf;

		# if we don't have a value set for $this->is_acf
		if( null === $this->is_acf ) {
			$this->is_acf = false;
			$this->load_acf_data();
		}

		return $this->is_acf;

	} # end: is_acf()

	/**
	 * Load ACF data for this field
	 */
	public function load_acf_data() {

			if( ! function_exists( 'get_field_object' ) ) return;

			# check the ACF field key pattern 
			if( preg_match("/field_[\dA-z]+/", $this->key ) ) {

				$acf_field = get_field_object( $this->key );

				# make sure the field isn't 'empty' (ACF returns an array with useles info if no field was found)
				if( '' == $acf_field['name'] ) return false;

				# set the object property
				$this->is_acf = true;

				/**
				 * Load the ACF data
				 */

				# set the key with the 'normal' field key (e.g. `email` replaces `field_abc123`)
				$this->key = $acf_field['name'];

				# set the position
				if( isset( $acf_field['order_no'] ) ) {
					$this->position = $acf_field['order_no'];
				}
				elseif( isset( $acf_field['menu_order'] ) ) {
					$this->position = $acf_field['menu_order'];
				}

				# set the label
				$this->label = $acf_field['label'];

				# set the field type
				$this->type = $acf_field['type'];

				## load the ACF array 
				$this->acf_field = $acf_field;
			
			} # end if: ACF field
	
	} # end: load_acf_data()

	/**
	 * Set/get all field values based on this field's key (alphabetically, using all post meta)
	 *
	 * @param 	bool 	$allow_empty 	Whether to allow empty values
	 * @since 	2.0.0
	 */
	 public function get_all_values( $allow_empty = true ) {

	 	# protect against hitting the DB 2 times
	 	if( null !== $this->all_values ) return $this->all_values;

	 	if( empty( $this->key ) ) return array();

	 	$key = sanitize_text_field( $this->key );

	 	# query the DB for field values
	 	global $wpdb;
	 	$field_values_query = 'SELECT DISTINCT meta_value ' . 
	 		' FROM ' . $wpdb->postmeta . 
	 		' WHERE meta_key="' . $this->key . '" ';

	 	# make sure not to get the empty string as a value from the DB
	 	$field_values_query .= ' AND meta_value != ""';

	 	# order alphabetically
	 	$field_values_query .= " ORDER BY meta_value ASC";

	 	$field_value_results = $wpdb->get_results( $field_values_query );

	 	# the array we'll output
	 	$values = array();

	 	# add the empty string as the first value if we're allowing empty
	 	if( $allow_empty ) $values[] = array( 'label' => 'Select', 'value' => '' );

	 	# loop through DB results and load the output array
	 	foreach( $field_value_results as $row ) {
	 		$values[] = $row->meta_value;
	 	}

	 	# set this instance's property and return tye array
	 	$this->all_values = $values;
	 	return $values;

	 } # end: get_all_values()

} # end class: BBD_Field