jQuery(document).ready(function($){
	// Browse button
	var oBrowse = $("input#cptdir-import-file");
	// Initialize to nothing
	//oBrowse.attr("value", "");
	// change event for Browse button
	oBrowse.change(function(){
		var sFile = $(this).attr("value");
		var msgDiv = $("#cptdir-file-message");
		// Check that .csv are last 4 characters of file
		if(sFile.substr(sFile.length - 4) !== ".csv"){
			$(this).attr("value", "");
			var msgDiv = $("#cptdir-file-message");
			msgDiv.removeClass("cptdir-success");
			msgDiv.addClass("cptdir-fail");
			msgDiv.html("Sorry, you must select a CSV file.");
		} // endif: not a csv file
		else{ 
			// CSV file successfully selected
			msgDiv.removeClass("cptdir-fail");
			msgDiv.addClass("cptdir-success");
			msgDiv.html("CSV file successfully selected.");
		}
		msgDiv.css("display", "block");		
	}); // end: change event for file upload field
	// click event for file submit button
	$("input#cptdir-file-submit").click(function(e){	
		// e.preventDefault();
		// Double check that we've got a CSV file
		var msgDiv = $("#cptdir-file-message");		
		var oBrowse = $("input#cptdir-import-file");
		var sFile = oBrowse.attr("value");
		if(sFile.substr(sFile.length - 4) !== ".csv"){
			msgDiv.css("display", "block");
			msgDiv.addClass("cptdir-fail");
			msgDiv.removeClass("cptdir-success");
			msgDiv.html("Sorry, you must select a CSV file.");
			return false;
		}
		// console.log(new FormData($("#cptdir-file-select")));
		// return false;
		// var file = $("input#cptdir-import-file").prop("files")[0];
		// filesy = $("#cptdir-file-select").files;
		/*
		var form = $("#cptdir-file-select");
		var inputs = form.find("input");
		form.html("");
		inputs.css("display", "none");
		inputs.each(function(){ form.append($(this)) });
		my_form = new FormData(form[0]);
//		var data = {action: "cptdir_import_js", form: my_form };
		$.ajax({
			type: "POST",
//			processData: false,
//			contentType: false,
			data: data,
			url: ajaxurl,
			beforeSend: function(){
				$("#cptdir-import-content").html("Importing file...");
			},
			success: function(data){
				$("#cptdir-import-content").html(data);
			},
			error: function (xhr, textStatus, errorThrown) {

                console.log(textStatus);

            }
		}); // ajax
		*/
		//return false;
		return;
	}); // click(): file submit button
	$( '#cptdir-file-select' ).submit( function( e ) {
		e.preventDefault();
		//console.log("Hey");
		var formData = new FormData(this);
		//var file = $(this).find("input")[0].files[0];
		//console.log(file);
		//formData.append("my_file", file);
		formData.append("action", "cptdir_import_js");
		$.ajax( {
		  url: ajaxurl,
		  type: 'POST',
		  data: formData,
		  processData: false,
		  contentType: false,
		beforeSend: function(){
			$("#cptdir-import-content").html("Importing file...");
		},	
		success: function(data){ 
			// Add response to content area
			$("#cptdir-import-content").html(data); 
			// take file and attach it to the main Import form
			var oFile = $("input#cptdir-import-file");
			var oForm = $("form#cptdir-import-form");
			var oClone = oFile.clone();
			oClone.css("display", "none").prependTo(oForm);
			
		}
		} );
		return;
	  } );	

	// change event for field select dropdowns
	$(document).on("change", ".cptdir-import-select", function(){
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
		$(".cptdir-import-select").each(function(){
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