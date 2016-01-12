jQuery( document ).ready( function($) {

	/**
	 * Search Widget
	 */

	// onchange for field checkboxes: display the field details section
	$( document).on( 'change', 'div#cptd-search-form div.cptd-search-widget-field input[type=checkbox]', 
		function() {
			toggleSearchFieldDetails( $, this );
	});

	// initialize field checkboxes
	$('div.cptd-search-widget-field').find('input[type=checkbox]')
		.each( function() { toggleSearchFieldDetails( $, this ) } );

	/**
	 * Random Posts Widget
	 */

	// onchange for taxonomy checkboxes: show terms details section
	$( document ).on( 'change', 'div#cptd-random-posts-form .taxonomy-select input[type=checkbox]', 
	 	function() {
	 		toggleTaxonomyTermDetails( $, this );
	 });

	// initialize taxonomy term details on page load
	$('div#cptd-random-posts-form .taxonomy-select input[type=checkbox]').each( function() {
		toggleTaxonomyTermDetails( $, this );
	});
}); // end: document ready


/**
 * Subroutines
 *
 * - toggleSearchFieldDetails()
 * - toggleTaxonomyTermDetails()
 */

/**
 * Callback for field checkbox onchange for search widget form
 * 
 * @param 	jQuery 	$			The main jQuery object
 * @param 	input 	checkbox	The field checkbox being toggled
 */
function toggleSearchFieldDetails( $, checkbox ) {

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

} // end: toggleSearchFieldDetails

/**
 * Callback for taxonomy checkbox onchange
 * 		- Makes an ajax call to load terms checkboxes if first time activated
 * 		- Toggles the terms checkbox area open or closed each additional time
 * 
 * @param 	jQuery 	$			The main jQuery object
 * @param 	input 	checkbox	The taxonomy checkbox being toggled
 */
function toggleTaxonomyTermDetails( $, checkbox ) {

	// the widget ID
	var widgetId = $(checkbox).closest('.cptd-widget-form').data('widget-id');
	var widgetNumber = $(checkbox).closest('.cptd-widget-form').data('widget-number');

	// the taxonomy post ID being toggled
	var taxId = $( checkbox ).val();

	// whether we're checking the checkbox (if false, we are unchecking)
	var on = $( checkbox ).prop('checked');

	// the terms checkboxes container for the taxonomy being checked or unchecked (may not exist)
		$termsDiv = $( checkbox ).closest('#cptd-random-posts-form')
			.find('div.cptd-terms-list[data-tax-id=' + taxId + ']');

	// if we're turning off the taxonomy checkbox
	if( ! on ) {

		if( $termsDiv.length > 0 ) {
			$termsDiv.css( {display: 'none'} );
		}
		return;
	}

	// if we are turning on the taxonomy checkbox
	else {

		// check if terms checkboxes already exist
		if(  $termsDiv.length > 0 ) {
			$termsDiv.css( {display: 'block'} );
			return;
		}

		// if not, do an AJAX request to get the terms checkboxes HTML		
		$.ajax( {
			url: ajaxurl,
			method: 'POST',
			data: {
				action: 'cptd_toggle_taxonomy_term_details',
				widget_id: widgetId,
				widget_number: widgetNumber,
				tax_id: taxId,
			},
			success: function( data ) {

				$label = $( checkbox ).closest('label');
				//var html = $.parseHTML( data )
				$( data ).insertAfter( $label );
			}
		}); // end: ajax
	} // end else: turning checkbox on

} // end: toggleTaxonomyTermDetails()