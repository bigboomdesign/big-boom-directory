// post title
var $title;

// the link to change the PT name
var $changeName;

// post type name input
var $handle;

// div that holds the input
var $handleContainer;

jQuery(document).ready(function($){
	$title = $('#title');
	$handle = $('#handle');
	$handleContainer = $('#handle-container');
	$changeName = $('a#change-name');
	
	// if we're starting with a blank post type name, fill it in when the title is edited for the first time
	/*
	if('' == $handle.val()){
		
		// go ahead and load the handle info if we're starting with a non-empty title
		if('' != $title.val()) triggerHandleInfo($);
		
		// title onblur event
		$title.on('blur', function(){
			// do nothing if title has been edited already and handle is non-empty
			if(bTitleEdit && '' != $handle.val()) return;

			// otherwise trigger the handle info
			triggerHandleInfo($);

			// set the title edit status to true
			bTitleEdit = true;
		});
	}
	*/
	$('a#change-name').on('click', function(){
		var bOn = $(this).data('active') == 'true';
		if(!bOn) $(this).data('active', 'true');
		else $(this).data('active', 'false');
		
		if(bOn){
			$handle.prop('readonly', false);
			triggerHandleInfo($);
			$changeName.html('Save');
		}
		else{
			$handle.prop('readonly', true);
			$changeName.html('Change');
			$handleContainer.removeClass('highlight');
			$handleContainer.find('#handle-info').css('display', 'none');
		}
	});
});
function triggerHandleInfo($){

	// get the title that was entered
	var title = $title.val();
	if('' == title) return;

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