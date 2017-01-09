/**
 * JavaScript for the Big Boom Directory Settings page
 *
 * Note that most of the methods here are also in the post-edit.js file, the differences
 * are:
 * 
 * 	- The $orderby and $metaKeyOrderbyContainer selectors differ
 * 	- The CSS property for the container is 'table-row' here instead of 'block' in post-edit.js
 * 	- Overall, there is much more JavaScript in the post-edit.js file to handle other features
 *
 * There are similarities between the two files that are good candidates for merging 
 * into a general bbd-admin.js file.  In particular, the three helper functions could be 
 * duplicated if there were a 'display' argument to pass in
 *
 * @see 	js/admin/bbd-post-edit.js
 * @since 	2.3.0
 */

// the select element for post orderby
var $orderby;

// the meta key orderby field that gets hidden and shown
var $metaKeyOrderbyContainer;

jQuery( document ).ready( function( $ ) {

	// orderby elements
	$orderby = $('select#post_orderby');
	$metaKeyOrderbyContainer = $( 'input#meta_key_orderby' ).closest( 'tr' );

	/**
	 * On change for post orderby
	 */
	$orderby.on( 'change', function() {
		orderbyChange();
	});
	
	// initialize the orderby
	orderbyChange();

} ); // end: document ready

/**
 * Orderby change routines
 *
 * - orderbyChange()
 * - orderbyCustomField()
 * - orderbyOther()
 */
function orderbyChange() {

	// if we're selecting the "Custom Field" option
	if( 'meta_value' == $orderby.val() || 'meta_value_num' == $orderby.val() ) {
		orderbyCustomField();
	}
	else{
		orderbyOther();
	}
}
function orderbyCustomField() {
	$metaKeyOrderbyContainer.css( 'display', 'table-row' );
} // end: orderbyCustomField()

function orderbyOther() {
	$metaKeyOrderbyContainer.css( 'display', 'none' );
} // end: orderbyOther()