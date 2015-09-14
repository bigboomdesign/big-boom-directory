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
	$title = $('#title');
	$handle = $('#handle');
	$handleContainer = $('#handle-container');
	$changeName = $('a#change-name');
	$cancelNameChange = $('div#cancel-name-change');
	
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
});
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
			action: 'cptd_post_type_meta_from_title',
			title: title
		},
		success: function(data){
			$handle.val(data);
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