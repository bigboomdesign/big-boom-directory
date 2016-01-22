jQuery( document ).ready( function( $ ) {

	// Onclick for 'Show Fields' link to show/hide checkbox container
	$( document ).on( 'click', '.show-hide-field-checkboxes', function() {
		toggleFieldCheckboxes( $, this );
	});

}); // end: document ready

/**
 * Onclick for 'Show Fields' link to show/hide checkbox container
 */
function toggleFieldCheckboxes( $, elem ) {
	
	// the clicked link
	var $link = $( elem );

	// the checkbox container to toggle
	var $checkboxes = $link.siblings( '.fields-checkboxes' );

	// if the checkboxes are currently visible
	if( 'block' == $checkboxes.css('display') ) {
		$checkboxes.css( { display: 'none' } );
		$link.html( 'Show Fields' );
	}

	// if the checkboxes are currently hidden
	else {
		$checkboxes.css( { display: 'block' } );
		$link.html( 'Hide Fields' );
	}

} // end: toggleFieldCheckboxes()