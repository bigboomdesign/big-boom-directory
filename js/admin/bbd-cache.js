/**
 * Event handlers for the Directory > Cache page in wp-admin
 *
 * @since 	2.2.0
 */
(function( $ ) {

	$(document).ready( function() {

		// onclick for "Flush Big Boom Directory Cache"
		$( '#bbd-flush-cache' ).on( 'click', function() {

			// where our response will go
			var responseDiv = $( '#bbd-flush-cache-response' );

			// clear out the response area
			responseDiv.html('');

			$.ajax( {
				url: ajaxurl,
				method: 'POST',
				data: {
					action: 'bbd_flush_post_type_cache',
					nonce: $('#bbd-cache-nonce #_wpnonce').val(),
					time: $('#bbd-cache-nonce #bbd-post-type-cache-time').val(),
				},
				success: function( data ) {
					responseDiv.html( data );
				}
			});

		}); // end: onclick for "Flush Big Boom Directory Cache"

		// onclick for "Save" (to disable/enable the plugin's cache use)
		$( '#bbd-save-cache-option' ).on( 'click', function() {

			// where our response will go
			var responseDiv = $( '#bbd-save-cache-option-response' );

			// clear out the response area
			responseDiv.html('');

			$.ajax( {
				url: ajaxurl,
				method: 'POST',
				data: {
					action: 'bbd_save_caching_option',
					nonce: $('#bbd-cache-nonce #_wpnonce').val(),
					time: $('#bbd-cache-nonce #bbd-post-type-cache-time').val(),
					disable_cache: ( $( '#bbd_disable_object_cache' ).prop('checked') ? 1 : 0 ),
				},
				success: function( data ) {
					responseDiv.html( data );
				}
			});

		}); // end: onclick for "Save" (to disable/enable the plugin's cache use)

	}); // end: document ready

})( jQuery );