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
	 * Whether to autodetect URLs (by default this is true for fields with key `web`, `website`, or `url`)
	 *
	 * @param	bool
	 * @since 	2.0.0
	 */
	var $auto_link = false;

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

		# autodetect
		if( 'web' == $this->key || 'website' == $this->key || 'url' == $this->key ) {
			$this->auto_link = true;
		}

	} # end: __construct()


	/**
	 * Display or get the HTML for this field (if $this->value is set)
	 *
	 * @param 	bool	$echo 	(Default: false) Prints the field HTML if set to true
	 * @since 	2.0.0
	 */
	public function get_html( $echo = false ) {

		global $post;

		$value = get_post_meta( $post->ID, $this->key, true );

		if( empty( $value ) ) return '';

		/**
		 * Special cases
		 * 
		 * - true === $this->auto_link
		 */

		# auto detect website field
		if( true === $this->auto_link ) {

			# do our best to make sure we have a valid URL
			if( 'http' != substr( $value, 0, 4 ) ) $value = 'http://' . $value;
		?>
			<div class="cptd-field text <?php echo $this->key; ?>">
					<a target="_blank" class='cptd-website-link' href="<?php echo $value; ?>" >
						View Website
					</a>
			</div>
		<?php
			return;
		} # end if: true === $this->auto_link

		# apply filter to value so users can edit it
		$value = apply_filters( 'cptd_field_value_' . $this->key, $value );

		# the field HTML
		?><div class="cptd-field <?php echo $this->type . " " . $this->key; ?>">
			<label><?php echo $this->label; ?>: &nbsp; </label><?php echo $value; ?>
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