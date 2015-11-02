<?php
/** 
 * Models a directory field to be displayed on the front end
 *
 * @since 	2.0.0
 */

class CPTD_Field {

	/**
	 * The field key (e.g. `email` or `field_abc123`)
	 *
	 * @param 	string
	 * @since 	2.0.0
	 */
	var $key = '';

	/**
	 * The field type
	 *
	 * @param	string
	 * @since 	2.0.0
	 */ 
	var $type = '';

	/**
	 * Whether this is an ACF field
	 *
	 * @param 	(bool|null)
	 * @since 	2.0.0
	 */
	var $is_acf = null;

	/**
	 * The ACF field array (if $this->is_acf)
	 *
	 * @param 	array
	 * @since 	2.0.0
	 */
	var $acf_field;

	/**
	 * Class methods
	 *
	 * - __construct()
	 * - get_html()
	 * - is_acf()
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

	} # end: __construct()


	/**
	 * Display or get the HTML for this field (if $this->value is set)
	 *
	 * @param 	bool			$echo 		(Default: false) Prints the field HTML if set to true
	 * @param 	(int|string) 	$post_id 	The post ID we are displaying the field for (default: global $post)
	 * @since 	2.0.0
	 */
	public function get_html( $echo = false, $post_id = '' ) {

		if( empty( $post_id ) ) {
			global $post;
			$post_id = $post->ID;
		}
		if( empty( $post_id ) ) return '';

		global $cptd_view;

		$value = '';

		if( isset( $cptd_view->post_meta[ $post_id ][ $this->key ] ) )
			$value = $cptd_view->post_meta[ $post_id ][ $this->key ];

		# apply filter to value so users can edit it
		$value = apply_filters( 'cptd_field_value_' . $this->key, $value );

		# apply filter to the label so users can edit it
		$label = array(
			'text' => $this->label,
			'before' => '<label>',
			'after' => ': &nbsp;</label>'
		);
		$label = apply_filters( 'cptd_field_label_' . $this->key, $label );

		/**
		 * Special cases
		 * 
		 * - auto detect social media fields
		 * - return if no value is in place
		 * - auto detect URL fields
		 * - images
		 * - date picker
		 * - gallery
		 */

		/**
		 * Auto detect social media fields
		 */
		if( $cptd_view->auto_detect_social ) {

			# see if we have a social media field key
			# if( in_array( $this->key, $cptd_view->auto_social_field_keys ) ) {
			foreach( $cptd_view->auto_social_field_keys as $possible_key ) 
			if( false !== strpos( $this->key, $possible_key ) ) {


				# if this is the first social media field, set the indicator and open up a wrapping div for the icons				
				if( 0 == count( $cptd_view->completed_social_fields ) ) {
					?>
					<div class='cptd-social-icons'>
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
				$cptd_view->completed_social_fields[] = $this->key;

				# check if this is the last social media field and close the wrapping div if so
				if( count( $cptd_view->completed_social_fields ) >= count( $cptd_view->social_fields_to_check ) ) {
					
					$cptd_view->completed_social_fields = array(); 
					?>
					</div><!-- .cptd-social-icons -->
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
		if( $cptd_view->auto_detect_url ) {

			# see if we have a website field key
			if( 'web' == $this->key || 'website' == $this->key || 'url' == $this->key ) {
				# do our best to make sure we have a valid URL
				if( 'http' != substr( $value, 0, 4 ) ) $value = 'http://' . $value;
			?>
				<div class="cptd-field text <?php echo $this->key; ?>">
						<a target="_blank" class='cptd-website-link' href="<?php echo $value; ?>" >
							<?php echo apply_filters('cptd_link_text', 'View Website', $this ); ?>
						</a>
				</div>
			<?php
				return;
			
			} # end if: website field key

		} # end if: auto detect website field


		/**
		 * Image field
		 */
		if( 'image' == $this->type ){

			$src = '';

			# get the appropriate size, falling back on thumbnail for archive and medium for single
			$size = ( isset( $cptd_view->image_size ) ? 
				$cptd_view->image_size :
				( 'archive' == $cptd_view->view_type  ? 
					'thumbnail' :
					'medium'
				)
			);
				

			# ACF gives the option of multiple save formats for images (object/url/id)
			if( $this->is_acf ) {

				switch( $this->acf_field['save_format'] ){

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

			# show image if we have a src
			if( $src ) {
				
				# make the image link to the listing page if we are on an archive page or search results view
				$link = '';

				if( 'archive' == $cptd_view->view_type ) {

					global $post;
					$link = get_permalink( $post->id );
				}
			?>
				<div class='cptd-field image <?php echo $this->key; ?>'>
				<?php
					if( $link ) {
					?>
						<a href="<?php echo $link; ?>">
					<?php
					}
					?>
							<img src="<?php echo $src; ?>" />
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

		/**
		 * Date picker field
		 */
		if( 'date_picker' == $this->type ) {

			# the format saved in ACF
			$format_in = $this->acf_field['date_format'];

			# conversion from JS to PHP
			$format_convert = array(
				'yymmdd' => 'Ymd',
				'dd/mm/yy' => 'd/m/Y',
				'mm/dd/yy' => 'm/d/Y',
				'yy_mm_dd' => 'Y_m_d'

			);
			# create the PHP date/time object
			$date = DateTime::createFromFormat($format_convert[ $format_in ], $value );

			# generate the value based on the ACF display type
			$value = $date->format( $format_convert[ $this->acf_field['display_format'] ] );
		
		} # end: date picker field

		/** 
		 * Gallery field
		 */
		if( 'gallery' == $this->type ) {

			# unserialize and make sure we have images
			$image_ids = unserialize( $value );
			if( ! $image_ids  ) return;

			# query the DB for all attachment metadata (so we don't have to call a WP function multiple times
			# that will hit the database for every image)
			global $wpdb;

			$image_query = "SELECT post_id, meta_value FROM " . $wpdb->postmeta . 
				" WHERE meta_key = '_wp_attachment_metadata' " . 
				" AND post_id IN ( " . implode( ", ", $image_ids ) . " )";

			$image_results = $wpdb->get_results( $image_query );

			if( ! $image_results ) return;

			# get the uploads directory URL
			$uploads_dir = wp_upload_dir();
			$uploads_url = $uploads_dir['baseurl'];

			# display the gallery
			$image_num = 0;
		?>
			<div class="cptd-gallery <?php echo $this->key; ?>">
			<?php
				foreach( $image_results as $row ) {

					$image_num++;
					
					$image_data = unserialize( $row->meta_value );

					if( ! isset( $image_data['sizes']['thumbnail']['file'] ) ) continue;

					$thumbnail_file = $image_data['sizes']['thumbnail']['file'];
					$full_size_file = $image_data['file'];

					$thumbnail_url = $uploads_url . '/' . $thumbnail_file;
					$full_size_url = $uploads_url . '/' . $full_size_file;
				?>
					<a 
						class="cptd-gallery-thumb <?php echo $image_num; ?>" 
						href="<?php echo $full_size_url; ?>" 
						data-lightbox="gallery-<?php echo $this->key; ?>"
					>
						<img class="cptd-image <?php echo $this->key . " " . $image_num; ?>" src="<?php echo $thumbnail_url; ?>" />
					</a>
				<?php
				} #end foreach: gallery image
			?>
			</div>
		<?php
			return;
		} # end: gallery field

		/**
		 * end: special cases
		 *
		 * At this point, all fields not addressed by special cases will be displayed the same way
		 */

		# output the field HTML
		?><div class="cptd-field <?php echo $this->type . " " . $this->key; ?>">
			<?php 
				echo $label['before'];
				echo $label['text'];
				echo $label['after'];
				echo $value;
			?>
		</div>
		<?php
	} # end: get_html()


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

			# check the ACF field key pattern 
			if( preg_match("/field_[\dA-z]+/", $this->key ) ) {

				$acf_field = get_field_object( $this->key );

				# make sure the field isn't 'empty' (ACF returns an array with useles info if no field was found)
				if( '' == $acf_field['name'] ) return false;

				# set the object property
				$this->is_acf = true;

				# load the ACF data

				## set the key with the 'normal' field key (e.g. `email` replaces `field_abc123`)
				$this->key = $acf_field['name'];

				$this->label = $acf_field['label'];

				$this->type = $acf_field['type'];

				## load the ACF array 
				$this->acf_field = $acf_field;
			
			} # end if: ACF field
	}

} # end class: CPTD_Field