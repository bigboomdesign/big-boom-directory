jQuery(document).ready(function($){
	$('.bbd-field.google_map').each(function(){
		var lat = $(this).data('lat');
		var lng = $(this).data('lng');
		var address = $(this).data('address');
		var zoom = $(this).data('zoom');

		// Create a map object and specify the DOM element for display.
		var map = new google.maps.Map(this, {
			center: {'lat': lat, 'lng': lng},
			scrollwheel: false,
			'zoom': zoom
		});

		// Create a marker and set its position.
		var marker = new google.maps.Marker({
			'map': map,
			position: map.center
		});

		var infowindow = new google.maps.InfoWindow({
			content: address,
			maxWidth: 300
		});
		
		infowindow.open(map, marker);
		
		marker.addListener('click', function() {
			infowindow.open(map, marker);
		});
	});
});