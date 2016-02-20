/**
 * Main routine
 */
jQuery( document ).ready( function($) {

	/**
	 * Search Widget
	 */

	// Onclick for 'Show Fields' links to show/hide checkbox container
	$( document ).on( 'click', '.show-hide-fields-area', function() {
		toggleFieldsArea( $, this );
	});

	// onchange for field checkboxes: display the field details section
	$( document).on( 'change', 'div#bbd-search-form div.bbd-search-field-filter-select div.bbd-search-widget-field input[type=checkbox]', 
		function() {
			toggleSearchFilterDetails( $, this );
	});

	// init routine that can be re-called when widget is saved
	initSearchWidget( $ );

}); // end: document ready


/**
 * Subroutines
 *
 * - initSearchWidget()
 * - toggleSearchFilterDetails()
 * - toggleFieldsArea()
 */

/**
 * Initialize the search widget on page load and when widget is saved
 *
 * @param 	jQuery 	$ 	The main jquery object
 * @since 	2.0.0
 */
function initSearchWidget( $ ) {

	// initialize field filter checkboxes: display field details for any checked fields
	$('div.bbd-search-field-filter-select div.bbd-search-widget-field').find('input[type=checkbox]')
		.each( function() { toggleSearchFilterDetails( $, this ) } );

	// draggable
	$( 'div.bbd-draggable-fields-container .bbd-fields-area .bbd-search-widget-field' ).draggable( {
		axis: 'y',
		scope: 'search-result-fields-scope',
		revert: 'invalid',
		helper: 'clone'
	});

	// droppable
	$( '.bbd-fields-drop' ).droppable( {
		scope: 'search-result-fields-scope',
		accept: '.bbd-search-widget-field',
		tolerance: 'touch',
		cursor: 'move',
		hoverClass: 'hover-over-draggable',
		drop: function( event, ui ) {
			
			//define items for use
			var drop_helper = $('.bbd-droppable-helper');
			var field_item = ui.draggable.clone(); 

			//on drop trigger actions
			field_item.find('.remove_item').addClass('active');

			// the name of the hidden field to append 
			var field_name = ui.draggable.find('label').data( 'field-name' );
			var field_key = ui.draggable.find('label').data('field-key');

			// append the hidden field and the 'Remove' helper div
			field_item.append('<div class="dashicons dashicons-no-alt bbd-remove-field"></div><input type="hidden" name="' + field_name + '[]" value="' + field_key + '"/>');
			
			//add this new item to the end of the droppable list
			drop_helper.before(field_item);
			drop_helper.removeClass('active');
			
			//trigger_remove_field_item_action();
			
		},
		over: function(event,ui){
			//when hovering over the droppable area, display the drop helper
			$('.bbd-fields-drop').find('.bbd-droppable-helper').addClass('active');
			
		},
		out: function(event,ui){
			$('.bbd-fields-drop').find('.bbd-droppable-helper').removeClass('active');
		}
	}); // end: droppable

	// sortable
	$( '.bbd-fields-drop' ).sortable( {
		items: '.bbd-search-widget-field',
		cursor: 'move',
		placeholder: 'bbd-search-fields-placeholder'
	});

	// onclick for removing fields from the droppable area
	$(document).on( 'click', 'div.bbd-remove-field', function() {
		$(this).closest( '.bbd-search-widget-field' )
			.hide( 'slow', function() { $(this).remove() } );
	});

} // end: initSearchWidget()

/**
 * Callback for field checkbox onchange for search widget form
 * 
 * @param 	jQuery 	$			The main jQuery object
 * @param 	input 	checkbox	The field checkbox being toggled
 */
function toggleSearchFilterDetails( $, checkbox ) {

	// whether we're checking the checkbox (if false, we are unchecking)
	var on = $( checkbox ).prop('checked');

	// the main container for this field
	var $container = $( checkbox ).closest( '.bbd-search-widget-field' );

	// the div we are showing or hiding
	var $target = $container.find('.field-type-select');

	// for turning on
	if( on ) {
		$target.css({display: 'block'});
		$container.addClass('bbd-highlight');

	} // end if: turning on

	// for turning off
	else {
		$target.css({display: 'none'});
		$container.removeClass('bbd-highlight');
	} // end else: turning off

} // end: toggleSearchFilterDetails

/**
 * Onclick for 'Show Fields' link to show/hide the fields container
 */
function toggleFieldsArea( $, elem ) {
	
	// the clicked link
	var $link = $( elem );

	// the parent container we are inside
	$container = $( elem ).closest('.bbd-draggable-fields-container');

	// the checkbox container to toggle
	var $checkboxes = $link.siblings( '.bbd-fields-area' );

	// the droppable area
	var $drop = $container.find('.ui-droppable');

	// if the checkboxes are currently visible, then hide them
	if( 'block' == $checkboxes.css('display') ) {

		$drop.css( { display: 'none' } );
		$checkboxes.css( { display: 'none' } );
		$link.html( 'Show Fields' );
	}

	// if the checkboxes are currently hidden, then show them
	else {
		if( $drop.length > 0 ) $drop.css( { display: 'block' } );
		$checkboxes.css( { display: 'block' } );
		$link.html( 'Hide Fields' );
	}

} // end: toggleFieldsArea()
