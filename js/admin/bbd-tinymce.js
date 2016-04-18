/**
 * Handles the response when the "Directory" button above the TinyMCE is clicked
 * Handles the response when shortcode parameters are submitted via the modal box
 *
 * @since   2.2.0
 */
jQuery( document ).ready( function( $ ) {

    // onclick for "Directory" button above the TinyMCE editor
    $('#insert-bbd-shortcode-button').on( 'click', function() {

        // the main editor object for the post content
        var editor = tinymce.editors.content;

        // invoke the editor's open function and configure the form for users to complete
        editor.windowManager.open( {

            // modal box title
            title: 'Add a Directory shortcode',

            // modal box content
            body: [

                // Select a shortcode from list
                {
                    type: 'listbox',
                    name: 'shortcode',
                    label: 'Shortcode',
                    values: [
                        { text: 'A to Z Listing', value: 'bbd-a-z-listing' },
                        { text: 'Terms List', value: 'bbd-terms' },
                        { text: 'Search Widget', value: 'bbd-search' }
                    ]
                },
            ], // end: body

            /**
             * Callback to invoke when the form is submitted
             *
             * Inserts the shortcode built by the user into the post content
             *
             * @param   e
             */
            onsubmit: function( e ) {
                console.log( e );
                editor.insertContent( '[' + e.data.shortcode + ']' );
            }
        });
    });

}); // end: document ready