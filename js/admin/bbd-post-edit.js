/**
 * JavaScript for the BBD post type edit screen
 *
 * @see 	js/admin/bbd-settings.js
 * @since 	2.0.0
 */

(function( $ ) {

// localized variables
var postId = bbdData.post_id;

// the select element for post orderby
var $orderby;

// the meta key orderby field that gets hidden and shown
var $metaKeyOrderbyContainer;

// the link to change the PT name
var $changeName;

// the link to cancel PT name change
var $cancelNameChange;

// post type name input
var $handle;

// last successfully saved post type handle
var ptName;

// div that holds the input
var $handleContainer;

// the reserved handle names that we won't allow
var reserved_handles = bbdData.reserved_handles;

// container div for slug change dialog
var $slugContainer;

// link to change slug
var $changeSlug;

// div with cancel/more info on slug change
var $cancelSlugChange;

// the slug input element
var $slug;

// the last successfully saved slug
var slug = '';

// the checkbox for showing REST API supported features
var $showInRestSupportCheckbox;

// the textbox for the REST Base argument
var $restBaseSupportInput;

// the checkbox for 'customize supported features'
var $enableSupportCheckbox;

// checkboxes for supported post type features
var $postTypeSupportCheckboxes;

// the meta box that holds the archive fields
var $archiveFieldsContainer;

// the meta box that holds the single fields
var $singleFieldsContainer;

jQuery( document ).ready( function( $ ) {

	/**
	 * Post type name/handle-related elements
	 */

	$orderby = $('#_bbd_meta_post_orderby');
	$metaKeyOrderbyContainer = $( '.cmb2-id--bbd-meta-meta-key-orderby' );

	// post type name/handle input
	$handle = $('#_bbd_meta_handle');

	// the original post type name 
	ptName = $handle.val();

	// container div for name change dialog
	$handleContainer = $('#handle-container');

	// link to change name
	$changeName = $('a#change-name');

	// div with  cancel/more info on name change
	$cancelNameChange = $('div#cancel-name-change');

	
	/**
	 * On change for post orderby
	 */
	$orderby.on( 'change', function() {
		orderbyChange();
	});
	
	// initialize the orderby
	orderbyChange();


	/**
	 * Post type name change (handle) interactions
	 */

	/* onclick for Change/Save link for post type handle */
	$('a#change-name').on('click', function(){

		// whether the 'Change Name' dialog box is activated
		var bOn = ( $( this ).data('active') == 'true' ) 
			|| ( 'undefined' == typeof $( this ).data('active') );
		
		// if dialog box has been activated
		if( bOn ) {
			triggerHandleInfo( $ );
		}

		// if dialog box has been deactivated
		else{
			hideHandleInfo( $, this );
		}
	});

	/* onclick for Cancel link for post type handle */
	$( 'div#cancel-name-change a' ).on( 'click', function() {

		$handle.val( ptName );
		hideHandleInfo( $, this );
	});


	/**
	 * Slug-related elements
	 */

	$slug = $('#_bbd_meta_slug');

	slug = $slug.val();

	$slugContainer = $('#slug-container');

	$changeSlug = $('#change-slug');

	$cancelSlugChange = $('div#cancel-slug-change');


	/**
	 * Slug change interactions
	 */

	/* onclick for Change/Save link for post type handle */
	$('a#change-slug').on('click', function(){

		// whether the 'Change Name' dialog box is activated
		var bOn = ($(this).data('active') == 'true') 
			|| ( 'undefined' == typeof $(this).data('active') );
		
		// if dialog box has been activated
		if( bOn ) {
			triggerSlugInfo( $ );
		}

		// if dialog box has been deactivated
		else{
			saveSlugInfo( $, this );
		}
	});
	
	/* onclick for Cancel link for post type slug */
	$( 'div#cancel-slug-change a' ).on( 'click', function() {
		$slug.val( slug );
		hideSlugInfo( $ );
	});

	/**
	 * REST API support interactions
	 */
	$showInRestSupportCheckbox = $( 'input#_bbd_meta_show_in_rest' );
	$restBaseSupportInput = $( 'div.cmb2-id--bbd-meta-rest-base' );

	$showInRestSupportCheckbox.on( 'click', function() {
		toggleShowInRestSupports( $ );
	} );
	toggleShowInRestSupports( $ );
	
	/**
	 * 'Post type supports' interactions
	 */
	$enableSupportCheckbox = $( 'input[value="enable_post_type_support"]' );
	$postTypeSupportCheckboxes = $( 'div.cmb2-id--bbd-meta-post-type-supports' )
		.find( 'input[value!="enable_post_type_support"]' );

	$enableSupportCheckbox.on( 'click', function() {
		togglePostTypeSupports( $ );
	} );
	togglePostTypeSupports( $ );

	/**
	 * Field group selection
	 *
	 * - Archive fields
	 * - Single fields
	 */

	/**
	 * Archive fields
	 */
	$archiveFieldsContainer = $('.cmb2-id--bbd-meta-pt-archive-fields');

	// onclick for archive field group checkbox
	$archiveFieldsContainer
		.find('input[type=checkbox].bbd_field_group_select')
		.on( 'click', function() {
			fieldGroupChange( $(this), 'acf_archive', $ );
		}
	); // end: onclick for archive field group checkbox

	// trigger any archive field group checkboxes that are already checked on page load
	$archiveFieldsContainer
		.find('input[type=checkbox].bbd_field_group_select:checked')
		.each( function() { fieldGroupChange( $(this), 'acf_archive', $ ); } );

	/**
	 * Single fields
	 */
	$singleFieldsContainer = $('.cmb2-id--bbd-meta-pt-single-fields');

	// onclick for single field group checkbox
	$singleFieldsContainer
		.find('input[type=checkbox].bbd_field_group_select')
		.on( 'click', function() {
			fieldGroupChange( $(this), 'acf_single', $ );
		}
	);

	// trigger any single field group checkboxes that are already checked on page load
	$singleFieldsContainer
		.find('input[type=checkbox].bbd_field_group_select:checked')
		.each( function() { fieldGroupChange( $(this), 'acf_single', $ ); } );

	/**
	 * Advanced Fields Setup
	 */

	// onclick for Edit Link Texts
	var $editLinkTextsLink = $( '#edit-link-texts a#init' );
	var $linkTextsRow = $('.cmb2-id--bbd-meta-url-link-texts');

	$editLinkTextsLink.on( 'click', function() {

		// toggle the link texts metabox area
		var isOpening = $linkTextsRow.css('display') === 'none';

		var newDisplay = isOpening ? 'block' : 'none';
		$linkTextsRow.css( { display: newDisplay } );

		var linkHtml = isOpening ? 'Hide Link Texts' : 'Edit Link Texts';
		$editLinkTextsLink.html( linkHtml );
	});

}); // end: on document ready

/**
 * Subroutines
 *
 * 		- Orderby change routines
 * 		- Name change routines
 * 		- Slug change routines
 * 		- REST API supports routines
 * 		- Post type supports routines
 * 		- Field group change routines
 */

/**
 * Orderby change routines
 *
 * - orderbyChange()
 * - orderbyCustomField()
 * - orderbyOther()
 */

/**
 * On change for the post type orderby parameter
 *
 * @since 	2.0.0
 */
function orderbyChange() {

	// if we're selecting the "Custom Field" option
	if( 'meta_value' == $orderby.val() || 'meta_value_num' == $orderby.val() ) {
		orderbyCustomField();
	}
	else{
		orderbyOther();
	}
}

/**
 * Show meta key input when ordering by custom field
 *
 * @since 	2.0.0
 */
function orderbyCustomField() {
	$metaKeyOrderbyContainer.css( 'display', 'block' );
} // end: orderbyCustomField()

/**
 * Hide meta key input when not ordering by custom field
 *
 * @since 	2.0.0
 */
function orderbyOther() {
	$metaKeyOrderbyContainer.css( 'display', 'none' );
} // end: orderbyOther()

 /**
  * Name change subroutines
  *
  * - getPostTitle()
  * - triggerHandleInfo()
  * - hideHandleInfo()
  */
function getPostTitle() {

	// classic editor
	var $title = $('input#title');

	// gutenberg
	if ($title.length === 0) {
		$title = $('.editor-post-title textarea');
	}

	if( $title.length === 0  ) {
		return '';
	}

	return $title.val() || $title.html();
}

/**
 * Enables the post type name input to be changed and pre-populates using post title if necessary
 *
 * @since 2.0.0
 */
function triggerHandleInfo( $ ){

	// get the current value of the post title
	var title = getPostTitle();

	$handle.prop('readonly', false);
	$changeName.html('Save');
	$changeName.data('active', 'false');
	$cancelNameChange.css('display', 'inline-block');

	// autopopulate the post type or taxonomy handle if the post title is non-empty and we have a new post type
	if( '' != title &&  0 == postId ) {

		$.ajax({
			url: ajaxurl,
			method: 'POST',
			type: 'POST',
			data: {
				action: 'bbd_handle_from_title',
				title: title
			},
			success: function( data ) {
				$handle.val( data );
			} 
		}); // end: ajax

	} // end if: autopopulating the handle

	$handle.focus();
	$handleContainer.addClass('highlight');
	$handleContainer.find('#handle-info').css('display', 'block');


} // end: triggerHandleInfo()

/**
 * Hide the handle change dialog box
 *
 * @param 	jQuery 	$		The main jQuery object
 * @param 	object	elem	The link being clicked
 *
 * @since 	2.0.0
 */
function hideHandleInfo( $, elem ) {

	// reset to previous post type name if handle is empty
	if( '' == $handle.val() ) {
		$handle.val( ptName );
	}
	
	// If we clicked 'Save'
	if( 'change-name' == $( elem ).attr('id') ) {

		// don't allow predefined post type or taxonomy names
		if( reserved_handles.indexOf( $handle.val() ) >= 0 ) {

			if( $( '#handle-info' ).find( '.bbd-fail' ).length == 0 ) {
				$('#handle-info').prepend( '<p class="bbd-fail">Sorry, but that name already exists or is not allowed.</p>' );
			}
			return;
		}

		// update the last successfully saved handle
		ptName = $handle.val();
	}

	$handle.prop('readonly', true);
	$changeName.html('Change');
	$changeName.data('active', 'true');
	$handleContainer.removeClass('highlight');
	$handleContainer.find('#handle-info').css('display', 'none');
	$cancelNameChange.css('display', 'none');

	$( '#handle-info' ).find('.bbd-fail').remove();

} // end: hideHandleInfo()


/**
 * Slug change subroutines
 * 
 * - triggerSlugInfo()
 * - saveSlugInfo()
 * - hideSlugInfo()
 */

/**
 * Enables the slug input to be changed
 *
 * @since 	2.0.0
 */
function triggerSlugInfo( $ ){

	// get the title that was entered
	var title = getPostTitle();
	if('' == title) return;

	$slug.prop('readonly', false);
	$changeSlug.html('Save');
	$changeSlug.data('active', 'false');
	$cancelSlugChange.css('display', 'inline-block');


	// for the slug
	$.ajax({
		url: ajaxurl,
		method: 'POST',
		type: 'POST',
		data: {
			action: 'bbd_slug_from_title',
			title: title,
		},
		success: function(data){

			if( '' == $slug.val() ) $slug.val(data);
			$slug.focus();
			$slugContainer.addClass('highlight');
			$slugContainer.find('#slug-info').css('display', 'block');

		} // end: success

	}); // end: ajax

} // end: triggerSlugInfo()

/**
 * Save the slug (validating first), and close the dialog box
 *
 * @since 	2.0.0
 */
function saveSlugInfo( $, elem ) {

	var newSlug = $slug.val();

	if( newSlug === slug ) {
		hideSlugInfo( $ );
		return;
	}

	$.ajax( {

		url: ajaxurl,
		method: 'POST',
		type: 'POST',
		data: {
			action: 'bbd_save_slug',
			slug: newSlug,
			id: postId,
		},

		success: function( data ) {

			// if we got back a 1, hide the dialogue area
			if( '1' == data ) {
				slug = newSlug;
				hideSlugInfo( $ );
			}

			// if we did not get back a 1, display the response message
			else {
				$('#slug-info').find( '.bbd-fail' ).remove();
				$('#slug-info').prepend( data );
				$slug.val( slug );
			}

		} // end: success()
	
	}); // end: ajax()

} // end: saveSlugInfo()

/**
 * Hide the slug information when saved or cancelled
 *
 * @since 	2.0.0
 */
function hideSlugInfo( $ ) {
	
	$slug.prop('readonly', true);
	$changeSlug.html('Change');
	$changeSlug.data('active', 'true');
	$slugContainer.removeClass('highlight');
	$slugContainer.find('#slug-info').css('display', 'none');
	$cancelSlugChange.css('display', 'none');

	$( '#slug-info' ).find('.bbd-fail').remove();

}

/**
 * REST API supports routines
 *
 *	- toggleShowInRestSupports()
 */

/**
 * Toggle the Show in REST supported features checkbox
 *
 * Shows the potential supported features for the REST API if checked, or hides them if unchecked.
 *
 * @since 	2.4.0
 */
function toggleShowInRestSupports( $ ) {
	if( $showInRestSupportCheckbox.prop( 'checked' ) ) {
		$restBaseSupportInput.css( 'display', 'block' );
	}
	else {
		$restBaseSupportInput.css( 'display', 'none' );
	}
}

/**
 * Post type supports routines
 *
 * 	- togglePostTypeSupports()
 */

/**
 * Toggle the 'Customize supported features' checkbox
 *
 * Shows the potential supported features if checked, or hides them if unchecked. We need
 * the master checkbox because we can't activate the feature on existing post type without
 * altering the existing supported features.
 *
 * @since 	2.3.0
 */
function togglePostTypeSupports( $ ) {
	if( $enableSupportCheckbox.prop( 'checked' ) ) {
		$postTypeSupportCheckboxes.each( function() {
			$( this ).closest('li').css( 'display', 'block' );
		});
	}
	else {
		$postTypeSupportCheckboxes.each( function() {
			$( this ).closest('li').css( 'display', 'none' );
		});
	}
}

/**
 * Field group change routines
 *
 * 	- fieldGroupChange()
 */

/**
 * Changes the available fields whenever a field groupd is selected
 *
 * @param 	jQuery 	$checkbox 		The checkbox being changed
 * @param 	string 	type 			(archive|single) Which view's field group is being toggled
 * @param	jQuery	$				The global jQuery object
 * @since 	2.0.0
 */
function fieldGroupChange($checkbox, type, $) {

	// the target for where our output is going to go
	var target = ( 'acf_archive' == type ) ? 
		$('#_bbd_meta_pt_archive_fields-field-results') : 
		( 'acf_single' == type ? $('#_bbd_meta_pt_single_fields-field-results') : '');

	// if we are turning the checkbox off
 	if( false == $checkbox.prop('checked') ) {
 		target.html('');
 		return;
 	}

 	// get the container of the field group being changed
	var $container = ( 'acf_archive' == type ) ? 
		$archiveFieldsContainer : 
		( 'acf_single' == type ? $singleFieldsContainer : '');

	if( '' == $container ) return;
	
	// uncheck all other checkboxes in this meta box
	$container
	.find('input[type=checkbox].bbd_field_group_select')
	.each( function() {
		if( $checkbox.attr('id') != $(this).attr('id') ) $(this).prop('checked', false);
	});

	// make an ajax call to populate the fields for this field group
	$.ajax( {
		url: ajaxurl,
		method: 'POST',
		type: 'POST',
		data: {
			action: 'bbd_select_field_group',
			post_id: postId,
			field_group_post_id: $checkbox.val(),
			view_type: type
		},
		success: function(data) {
			// populate the proper container with the field checkboxes
			target.html(data);
		}
	}); // end: ajax()
} // end: fieldGroupChange()

})(jQuery);