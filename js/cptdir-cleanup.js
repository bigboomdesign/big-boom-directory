jQuery(document).ready(function($){
	// remove all data for a single field
	$(".cptdir-remove-field").click(function(){
		var field = $(this).attr("data-field");
		var anchor = $(this);
		//console.log($(this).attr("data-field"));
		if(field){
			$.ajax({
				type: "POST",
				data: "cptdir_field="+$(this).attr("data-field") + "&action=cptdir_remove_field",
				url: ajaxurl,
				beforeSend: function(){
					$("#cptdir-remove-" + field + "-message").html("Removing field...");
				},
				success: function(data){
					$("#cptdir-remove-" + field + "-message").html(data);
					anchor.hide();
				}
			}); // ajax
		} // endif: field is set
	}); // end onclick: remove field
	
	// remove all custom field data
	$('button#remove-all-postmeta').on('click', function(){
		$.post(
			ajaxurl,
			{
				action: 'cptdir_remove_all_field_data',
			},
			function(response){
				$('#cptdir-remove-all-fields-messsage').html(response);
			}
		);
	}); // end onclick: remove all custom field data
	// remove unpublished
	$('button#remove-unpublished').on('click', function(){
		$.post(
			ajaxurl,
			{
				action: 'cptdir_remove_unpublished',
			},
			function(response){
				$('#cptdir-remove-unpublished-messsage').html(response);
			}
		);
	});
	// remove published
	$('button#remove-published').on('click', function(){
		$.post(
			ajaxurl,
			{
				action: 'cptdir_remove_published',
			},
			function(response){
				$('#cptdir-remove-published-messsage').html(response);
			}
		);
	});	
});