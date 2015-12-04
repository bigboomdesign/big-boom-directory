jQuery( document ).ready( function($) {

	// onchange for field checkboxes
	$(document).on( 'change', 'div.cptd-search-widget-field input[type=checkbox]', function() {
		openCloseCheckboxDetails( $, this );
	});
/*
	$('div.cptd-search-widget-field').find('input[type=checkbox]')
	.on('change', function() {
		
	});
*/

	// initialize field checkboxes
	$('div.cptd-search-widget-field').find('input[type=checkbox]')
		.each( function() { openCloseCheckboxDetails( $, this ) } );
});


/**
 * Subroutines
 *
 * - openCloseCheckboxDetails()
 */

/**
 * Callback for field checkbox onchange
 * 
 * @param 	jQuery 	$			The main jQuery object
 * @param 	input 	checkbox	The checkbox being toggled
 */
function openCloseCheckboxDetails( $, checkbox ) {

	// whether we're checking the checkbox (if false, we are unchecking)
	var on = $( checkbox ).prop('checked');

	// the main container for this field
	var $container = $( checkbox ).closest( '.cptd-search-widget-field' );

	// the div we are showing or hiding
	var $target = $container.find('.field-type-select');

	// for turning on
	if( on ) {
		$target.css({display: 'block'});
		$container.addClass('cptd-highlight');
	} // end if: turning on

	// for turning off
	else {
		$target.css({display: 'none'});
		$container.removeClass('cptd-highlight');
	} // end else: turning off

} // end: openCloseCheckboxDetails