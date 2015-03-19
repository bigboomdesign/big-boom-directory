jQuery(document).ready(function($){
	$('#map-custom-fields-to-acf').click(function(){
		$.ajax(
			ajaxurl,
			{
				'data' : {
					'action': 'map-custom-fields-to-acf'
				},
				'success': function(data){
					$('#map-fields-message').html(data);
				}	
			}
		);
	});
});