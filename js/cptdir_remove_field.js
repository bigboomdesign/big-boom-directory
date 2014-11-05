jQuery(document).ready(function($){
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
	}); // click
}); // jquery