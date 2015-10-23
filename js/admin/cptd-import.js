jQuery(document).ready(function($){
	// Browse button
	var oBrowse = $("input#cptd-import-file");
	// Initialize to nothing
	//oBrowse.attr("value", "");
	// change event for Browse button
	oBrowse.change(function(){
		var sFile = $(this).attr("value");
		var msgDiv = $("#cptd-file-message");
		// Check that .csv are last 4 characters of file
		if(sFile.substr(sFile.length - 4) !== ".csv"){
			$(this).attr("value", "");
			var msgDiv = $("#cptd-file-message");
			msgDiv.removeClass("cptd-success");
			msgDiv.addClass("cptd-fail");
			msgDiv.html("<p class='cptd-fail'>Sorry, you must select a CSV file.</p>");
		} // endif: not a csv file
		else{ 
			// CSV file successfully selected
			msgDiv.removeClass("cptd-fail");
			msgDiv.addClass("cptd-success");
			msgDiv.html("CSV file successfully selected.");
		}
		msgDiv.css("display", "block");		
	}); // end: change event for file upload field

	// Make sure .csv is valid when file submit button is clicked
	$("input#cptd-file-submit").click(function(e){	
		// e.preventDefault();
		// Double check that we've got a CSV file
		var msgDiv = $("#cptd-file-message");		
		var oBrowse = $("input#cptd-import-file");
		var sFile = oBrowse.attr("value");
		if(sFile.substr(sFile.length - 4) !== ".csv"){
			msgDiv.css("display", "block");
			msgDiv.addClass("cptd-fail");
			msgDiv.removeClass("cptd-success");
			msgDiv.html("Sorry, you must select a CSV file.");
			return false;
		}
		return;
	}); // end click(): file submit button
	
	// process the .csv file and post type option
	$( '#cptd-file-select' ).submit( function( e ) {
		e.preventDefault();
		var formData = new FormData(this);
		formData.append("action", "cptd_import_csv_submit");
		formData.append('post_type', $('select#cptd-import-post-type').attr('value'));
		$.ajax( {
		  url: ajaxurl,
		  type: 'POST',
		  data: formData,
		  processData: false,
		  contentType: false,
		beforeSend: function(){
			$("#cptd-import-content").html("Importing file...");
		},	
		success: function(data){ 
			// Add response to content area
			$("#cptd-import-content").html(data); 
			// take file and attach it to the main Import form
			var oFile = $("input#cptd-import-file");
			var oForm = $("form#cptd-import-form");
			var oClone = oFile.clone();
			oClone.css("display", "none").prependTo(oForm);
			
		}
		} );
		return;
	  } );	

	// onclick for "title_use_merge_tag"
	$(document).on('click', 'input[name=title_method]', function(){
		// the <div> that opens/closes
		var mergeTag = $('#title_define_merge_tag');
		
		// if we're selecting 'merge tag'
		if($(this).val() == 'merge_tag'){
			// open the merge tag input
			mergeTag.css('display', 'block');
			$('option[value=post_title]').prop('disabled', true);
		}
		else{
			// close the merge tag input
			mergeTag.css('display', 'none');
			$('option[value=post_title]').prop('disabled', false);
		}
	}); // end click: use merge tag for title
	
	// change event for field select dropdowns
	$(document).on("change", ".cptd-import-select", function(){
		// Are we resetting a select box?
		var bReset = false;
		
		// Get old value from data-field so we can restore options in other dropdowns if necessary
		var sOldValue = $(this).attr("data-field");
		// Get value that's just been selected
		var oSelect = $(this);
		var value = oSelect.attr("value");
		if(value == ""){ bReset = true; }
		// Change to green if we're setting the value
		if(!bReset) $(this).css("background-color", "#00ee00");
		// Change to white if we're resetting
		else{ $(this).css("background-color", "#fff"); }
		// Loop over dropdowns and disable this value
		$(".cptd-import-select").each(function(){
			// Only take action if we're not dealing with the <select> that's just been set, or if we're resetting
			if($(this).attr("id") != oSelect.attr("id") || bReset){
				// Loop through options
				$(this).find("option").each(function(){
					// If resetting, make sure we only reset the right value
					if(bReset){ if(sOldValue == $(this).attr("value")) $(this).attr("disabled", false); }
					// If not resetting, locate the option that's just been selected and disable it
					else if($(this).attr("value") == oSelect.attr("value")){ $(this).attr("disabled", "disabled"); }
				});
			}
		});
		// update <select> data-field with new value
		$(this).attr("data-field", $(this).attr("value"));
	});
});