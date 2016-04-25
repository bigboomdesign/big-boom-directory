/**
 * Handles the Google Map integration with the ACF Google Map field type
 * Applies to both single posts and archive posts, whenever the Google Map field type is used
 *
 * @since 	2.2.0
 */
jQuery(document).ready(function($){

	// iterate through all google map containers found in the document
	$('.bbd-field.google_map').each(function(){

		// get the data pertaining to this map container
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

		// Add an infowindow to the marker
		var infowindow = new google.maps.InfoWindow({
			content: address,
			maxWidth: 300
		});

		// Make sure the infowindow is open by default
		infowindow.open(map, marker);

		// Open the infowindow when the marker is clicked
		marker.addListener('click', function() {
			infowindow.open(map, marker);
		});

	}); // end each: google map containers in the document

}); // end: document ready