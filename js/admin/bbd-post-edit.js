// localized variables
var postId = bbdData.postId

// post title input
var $title;

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

// original post type name for this page load
var ptName;

// div that holds the input
var $handleContainer;

// the meta box that holds the archive fields
var $archiveFieldsContainer;

// the meta box that holds the single fields
var $singleFieldsContainer;

jQuery( document ).ready( function( $ ) {

	// post title input
	$title = $('#title');

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
		var bOn = ($(this).data('active') == 'true') 
			|| ( 'undefined' == typeof $(this).data('active') );
		
		// if dialog box has been activated
		if(bOn){
			triggerHandleInfo( $ );
		}

		// if dialog box has been deactivated
		else{
			hideHandleInfo( $ );
		}
	});

	/* onclick for Cancel link for post type handle */
	$( 'div#cancel-name-change a' ).on( 'click', function() {
		$handle.val( ptName );
		hideHandleInfo($);

	});


	/**
	 * Slug-related elements
	 */ 

	// slug input
	$slug = $('#_bbd_meta_slug');

	// container div for slug change dialog
	$slugContainer = $('#slug-container');

	// link to change slug
	$changeSlug = $('#change-slug');

	// div with cancel/more info on slug change
	$cancelSlugChange = $('div#cancel-slug-change');


	/**
	 * Slug change interactions
	 */

	/* onclick for Change/Save link for post type handle */
	$('a#change-slug').on('click', function(){

		// get the current input value
		$ptSlug = $slug.val();

		// whether the 'Change Name' dialog box is activated
		var bOn = ($(this).data('active') == 'true') 
			|| ( 'undefined' == typeof $(this).data('active') );
		
		// if dialog box has been activated
		if(bOn){
			triggerSlugInfo( $ );
		}

		// if dialog box has been deactivated
		else{
			hideSlugInfo( $ );
		}
	});
	
	/* onclick for Cancel link for post type handle */
	$( 'div#cancel-slug-change a' ).on( 'click', function() {
		$slug.val( $ptSlug );
		hideSlugInfo($);

	});

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
	$('#edit-link-texts a#init').on( 'click', function() {

		// toggle the link texts metabox area
		var $linkTextsRow = $('.cmb2-id--bbd-meta-url-link-texts');
		var display = ( $linkTextsRow.css('display') == 'none' ) ? 'block' : 'none';
		$linkTextsRow.css( {'display': display } );
	});

}); // end: on document ready

/**
 * Subroutines
 *
 * - Orderby change routines
 * - Name change routines
 * - Slug change routines
 * - Field group change routines
 */

/**
 * Orderby change routines
 *
 * - orderbyChange()
 * - orderbyCustomField()
 * - orderbyOther()
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
function orderbyCustomField() {
	$metaKeyOrderbyContainer.css( 'display', 'block' );
} // end: orderbyCustomField()

function orderbyOther() {
	$metaKeyOrderbyContainer.css( 'display', 'none' );
} // end: orderbyOther()

 /**
  * Name change subroutines
  *
  * - triggerHandleInfo()
  * - hideHandleInfo()
  */

 /**
  *  Enables the post type name input to be changed and pre-populates using post title if necessary
  */
function triggerHandleInfo( $ ){

	// get the current value of the post title
	var title = $title.val();
	if('' == title) return;

	$handle.prop('readonly', false);
	$changeName.html('Save');
	$changeName.data('active', 'false');
	$cancelNameChange.css('display', 'inline-block');


	// for the handle
	$.ajax({
		url: ajaxurl,
		method: 'POST',
		data: {
			action: 'bbd_handle_from_title',
			title: title
		},
		success: function( data ) {

			// only autopopulate based on post title if the handle hasn't been edited yet by the user
			if( '' == $handle.val() || $handle.val().indexOf( 'bbd_pt_' ) > -1 || $handle.val().indexOf( 'bbd_tax_' ) > -1) {
				$handle.val( data );
			}
			$handle.focus();
			$handleContainer.addClass('highlight');
			$handleContainer.find('#handle-info').css('display', 'block');
		} // end: success
	}); // end: ajax

} // end: triggerHandleInfo()

/**
 * Hide the handle change dialog box
 */
function hideHandleInfo( $ ) {

	// reset to previous post type name if handle is empty
	if( '' == $handle.val() ) {
		$handle.val( ptName );
	}
	$handle.prop('readonly', true);
	$changeName.html('Change');
	$changeName.data('active', 'true');
	$handleContainer.removeClass('highlight');
	$handleContainer.find('#handle-info').css('display', 'none');
	$cancelNameChange.css('display', 'none');
} // end: hideHandleInfo()


/**
 * Slug change subroutines
 * 
 * - triggerSlugInfo()
 * - hideSlugInfo()
 */

/**
 * Enables the slug input to be changed
 */
function triggerSlugInfo( $ ){

	// get the title that was entered
	var title = $title.val();
	if('' == title) return;

	$slug.prop('readonly', false);
	$changeSlug.html('Save');
	$changeSlug.data('active', 'false');
	$cancelSlugChange.css('display', 'inline-block');


	// for the handle
	$.ajax({
		url: ajaxurl,
		method: 'POST',
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
 * Hide the slug change dialog box
 */
function hideSlugInfo( $ ) {
	$slug.prop('readonly', true);
	$changeSlug.html('Change');
	$changeSlug.data('active', 'true');
	$slugContainer.removeClass('highlight');
	$slugContainer.find('#slug-info').css('display', 'none');
	$cancelSlugChange.css('display', 'none');
} // end: hideSlugInfo()


/**
 * Field group change routines
 *
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