<?php
class CPTD_pt{
	var $post = ''; // instance of WP_Post
	var $ID = 0; // (int) Shortcut to $this->post->ID
	
	var $meta = array(); // the unserialized array from the `cptd_post_meta` custom field for $this->post that CPTD needs to operate

	function __construct($post){
		# if we're being passed a WP_Post object
		if(is_a($post, 'WP_Post')){
			$this->ID = $post->ID;
			$this->post = $post;
		}

		# if we're being passed an ID
		elseif($ID = intval($post)){
			$post = get_post($ID);
			if($post){
				$this->ID = $ID;
				$this->post = $post;
			}
		}
	}
	
	# load the CPTD post meta for this post into $this->meta
	public function get_cptd_meta(){
		$meta = get_post_meta($this->ID, 'cptd_post_meta', true);

		$this->meta = shortcode_atts(
			array(
				'handle' => 'cptd_pt_'.$this->ID,
				'singular' => '',
				'plural' => $this->post->post_title
			),
			$meta,
			'cptd_post_meta'
		);
	} # end: get_cptd_meta()
	
	/*
	* Meta boxes for post edit screens
	*/
	
	public static function add_meta_boxes($post_type, $post) {
		# only add boxes for CPTD post type
		if('cptd_pt' != $post_type) return;
		
		add_meta_box(
			'cptd_post_type_meta_box',
			'Post Type Settings',
			array('CPTD_pt', 'post_type_meta_box'),
			'cptd_pt',
			'normal',
			'high'
		);

		# the code below removes the default editor, and then re-adds it so that our meta box is at the top
		global $_wp_post_type_features;
		if (isset($_wp_post_type_features[$post_type]['editor']) && $_wp_post_type_features['post']['editor']) {
			unset($_wp_post_type_features[$post_type]['editor']);
			add_meta_box(
				'description_section',
				__('Post Type Description'),
				array('CPTD_pt', 'post_content_box'),
				'cptd_pt', 'normal', 'high'
			);
		}
	} # end: add_meta_boxes()

	# The post type settings meta box
	public static function post_type_meta_box($post){
		
		$pt = new self($post);
		$pt->get_cptd_meta();
	
		// Add a nonce field so we can check for it later.
		wp_nonce_field( 'cptd_pt_save_meta_box_data', 'cptd_meta_box_nonce' );
		
		ob_start();
		?>
		<div id='cptd_post_meta_box' >
			<div class='field' id='handle-container'>
				<label for="handle">
					Name <span class='required'>*</span><br />
					<input 
						autocomplete='off' 
						type='text' 
						class='regular-text' 
						id='handle'
						name='cptd_post_meta[handle]'  
						value="<?php echo $pt->meta['handle']; ?>" 
						readonly="readonly"
					/>
					<div id='change-cptd-name-container'>
						<a id='change-pt-name'>Change</a>
						<div id='change-pt-name-dialogue' style='display: none;'>
							<p>Really?</p>
						</div>
					</div>
				</label>
				<div id='handle-info' style='display: none;'>
					<p class="description">The Post Type Name is the most important part of your post type. Once it is set and you have created posts for your post type, this value should not be changed.</p>
					<p class="description">We guessed the ideal Post Type Name based on your title.  If you edit this field, please use only lowercase letters and underscores, and use a singular name like <code>book</code> instead of a plural name like <code>books</code>.</p>
				</div>
			</div>
			<div class='field'>
				<label for="singular">
				Singular Label<br />
				<input type='text' name="cptd_post_meta[singular]" id='singular' class='regular-text' value="<?php echo $pt->meta['singular']; ?>" />
				</label>
				<p class='description'>Ex: <code>Book</code></p>
			</div>
			<div class='field'>
				<label for="plural">
				Plural Label<br />
				<input type='text' class='regular-text' name='cptd_post_meta[plural]' id='plural' value="<?php echo $pt->meta['plural']; ?>"  />
				</label>
				<p class='description'>Ex: <code>Books</code></p>
			</div>
		</div>
		<?php
		$html = ob_get_contents();
		ob_end_clean();
		echo $html;
	} # end: post_type_meta_box()
	
	# The post type description meta box
	public static function post_content_box( $post ) {
		do_action('edit_form_advanced', $post);
		#wp_editor($post->post_content, 'editpost');
	}
	
	public static function save_post_type_meta_box_data( $post_id ) {

		/*
		 * We need to verify this came from our screen and with proper authorization,
		 * because the save_post action can be triggered at other times.
		 */

		// Check if our nonce is set.
		if ( ! isset( $_POST['cptd_meta_box_nonce'] ) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['cptd_meta_box_nonce'], 'cptd_pt_save_meta_box_data' ) ) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check the user's permissions.
		if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {

			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return;
			}

		} else {

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
		}

		/* OK, it's safe for us to save the data now. */

		// Make sure that required fields are
		if(empty( $_POST['cptd_post_meta']['handle'])) {
			add_settings_error(
				'missing-pt-name',
				'missing-pt-name',
				'You have not specified a Post Type Name.',
				'error'
			);

			set_transient( 'settings_errors', get_settings_errors(), 30 );
			return false;
		}
		
		// Sanitize user input.
		$cptd_post_meta = $_POST['cptd_post_meta'];
		foreach($cptd_post_meta as $k => &$value){
			$value = sanitize_text_field($value);
		}
		
		// Update the meta field
		update_post_meta($post_id, 'cptd_post_meta', $cptd_post_meta);
	} # end: save_post_type_meta_box_data()
	public static function post_type_admin_notices(){
		// If there are no errors, then we'll exit the function
		if ( ! ( $errors = get_transient( 'settings_errors' ) ) ) {
			return;
		}
		// Otherwise, build the list of errors that exist in the settings errores
		$message = '<div id="cptd-post-type-edit-message" class="error notice"><ul>';
		foreach ( $errors as $error ) {
		$message .= '<li>' . $error['message'] . '</li>';
		}
		$message .= '</ul></div><!-- #error -->';
		// Write them out to the screen
		echo $message;
		
		// Clear and the transient and unhook any other notices so we don't see duplicate messages
		delete_transient( 'settings_errors' );
		remove_action( 'admin_notices', array('CPTD_pt', 'post_type_admin_notices'));
	} # end: post_type_admin_notices()
} # end: CPTD_pt