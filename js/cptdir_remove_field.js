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
					anchor.parent().css("opacity", "0.4");
					/*
					if(data == "valid"){
						$("#cptdir-remove-" + $(this).attr("data-field") + "-message").html("Email OK");				
					}
					else{
						$("#emailInfo").html("Invalid email");				
					}
					*/
				}
			}); // ajax
		} // endif: field is set
	}); // click
}); // jquery