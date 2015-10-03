// post title
var $title;

// the link to change the PT name
var $changeName;

// the link to cancel PT name change
var $cancelNameChange;

// post type name input
var $handle;

// original post type name
var $ptName;

// div that holds the input
var $handleContainer;

jQuery(document).ready(function($){

	// post title input
	$title = $('#title');

	/**
	 * Post type name/handle-related elements
	 */

	// post type name/handle input
	$handle = $('#_cptd_meta_handle');

	// container div for name change dialog
	$handleContainer = $('#handle-container');

	// link to change name
	$changeName = $('a#change-name');

	// div with  cancel/more info on name change
	$cancelNameChange = $('div#cancel-name-change');

	
	/**
	 * Post type name change interactions
	 */

	/* onclick for Change/Save link for post type handle */
	$('a#change-name').on('click', function(){

		// get the current input value
		$ptName = $handle.val();

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
		$handle.val( $ptName );
		hideHandleInfo($);

	});


	/**
	 * Slug-related elements
	 */ 

	// slug input
	$slug = $('#_cptd_meta_slug');

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
});

/**
 * Subroutines
 *
 * - Name change routines
 * - Slug change routines
 */

 /**
  * Name change routines
  */

 /**
  *  Enables the post type name input to be changed and pre-populates with 
  */
function triggerHandleInfo( $ ){

	// get the title that was entered
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
			action: 'cptd_post_type_handle_from_title',
			title: title
		},
		success: function(data){
			if('' == $handle.val() || $handle.val().indexOf( 'cptd_pt_' ) > -1) $handle.val(data);
			$handle.focus();
			$handleContainer.addClass('highlight');
			$handleContainer.find('#handle-info').css('display', 'block');
		} // end: success
	}); // end: ajax
}

function hideHandleInfo( $ ) {
	$handle.prop('readonly', true);
	$changeName.html('Change');
	$changeName.data('active', 'true');
	$handleContainer.removeClass('highlight');
	$handleContainer.find('#handle-info').css('display', 'none');
	$cancelNameChange.css('display', 'none');
}

/**
 * Slug change subroutines
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
			action: 'cptd_post_type_slug_from_title',
			title: title,
		},
		success: function(data){
			if( '' == $slug.val() ) $slug.val(data);
			$slug.focus();
			$slugContainer.addClass('highlight');
			$slugContainer.find('#slug-info').css('display', 'block');
		} // end: success
	}); // end: ajax
}

function hideSlugInfo( $ ) {
	$slug.prop('readonly', true);
	$changeSlug.html('Change');
	$changeSlug.data('active', 'true');
	$slugContainer.removeClass('highlight');
	$slugContainer.find('#slug-info').css('display', 'none');
	$cancelSlugChange.css('display', 'none');
}