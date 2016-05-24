/**
 * Inserts the Directory button and the modal HTML into the document
 * Handles the response when the "Directory" button above the TinyMCE is clicked
 * Handles interaction events for selecting shortcode parameters within the modal
 * Handles the response when shortcode parameters are submitted via the modal box
 *
 * @since   2.2.0
 */

/**
 * Insert the Directory button and the modal box HTML into the document
 */
(function( $ ) {

    var iconUrl = BBD_Shortcode_Data.icon_url,
        postTypes = BBD_Shortcode_Data.post_types,
        taxonomies = BBD_Shortcode_Data.taxonomies;

    var shortcodeBuilder = {

        button: document.createElement( 'button' ),
        icon: document.createElement( 'span' ),
        modal: document.createElement( 'div' ),

        init: function() {


            this.button.type = 'button';
            this.button.id = "insert-bbd-shortcode";
            this.button.className = 'button insert-bbd-shortcode';
            this.button.setAttribute('data-editor', 'content');

            $(this.icon).css( {
                backgroundImage: 'url( ' + iconUrl + ' )',
                backgroundSize: '100% 100%',
                width: '18px',
                height: '18px',
                marginRight: '0.3rem',
                display: 'inline-block',
                verticalAlign: 'text-top'
            });

            this.button.innerHTML = this.icon.outerHTML + 'Directory';

            this.modal.className = 'bbd-shortcode-modal-wrap';
            this.modal.innerHTML = '<div class="bbd-shortcode-modal-inner"><button id="modal-close" type="button" class="button-link media-modal-close"><span class="media-modal-icon"><span class="screen-reader-text">Close shortcode builder panel</span></span></button></div>';
        },

        ready: function() {

            this.buttonParent = document.getElementById('wp-content-media-buttons');
            this.buttonParent.appendChild( this.button );

            document.body.appendChild( this.modal );

            this.$button = $( this.button );
            this.$modal = $( this.modal );

            this.$modal.on('click', function(event){

                $('.bbd-shortcode-modal-wrap').removeClass('active');

            });

            this.$button.on('click', function(event){
                event.preventDefault();

                $('.bbd-shortcode-modal-wrap').addClass('active');
            });
        }

    }; // end: shortcodeBuilder

    shortcodeBuilder.init();

    $(document).ready(function() {
        shortcodeBuilder.ready();
    });
})( jQuery );

 /**
  * clip
  *
jQuery( document ).ready( function( $ ) {

    /**
     * Collect the dynamic data we are being passed
     *
    var data = BBD_Shortcode_Data;
    var post_types = data.post_types;
    var taxonomies = data.taxonomies;

    // onclick for "Directory" button above the TinyMCE editor
    $('#insert-bbd-shortcode-button').on( 'click', function() {

        // the main editor object for the post content
        var editor = tinymce.editors.content;

        /**
         * Configure the form for users to complete and invoke the editor's open() method 
         *

        // arguments to pass to the open() method
        var openArgs = {

            // modal box title
            title: 'Add a Directory Shortcode',

            // modal box content
            body: [

                // Select a shortcode from list
                {
                    type: 'listbox',
                    name: 'shortcode',
                    label: 'Shortcode',
                    id: 'select-shortcode',
                    values: [
                        { text: 'A to Z Listing', value: 'bbd-a-z-listing', id: 'a-z-listing' },
                        { text: 'Terms List', value: 'bbd-terms', id: 'terms' },
                        { text: 'Search Widget', value: 'bbd-search', id: 'search' }
                    ],
                    /**
                     * Callback to invoke when the <select> element is changed
                     *
                     * Shows and hides the various fields based on which shortcode is selected
                     *
                     * @param   e       The event triggered on selection
                     *
                    onselect: function( e ) {
                        
                        var new_value =  this.value();

                        /**
                         * If selecting the A to Z Listing
                         *
                        if( 'bbd-a-z-listing' == new_value ) {

                            $( 'h1.post-type' ).closest('.mce-container').show().css('position', 'absolute');
                            $( '.mce-post-type' ).closest('.mce-container').show().css('position', 'absolute');

                            editor.windowManager.open( openArgs );

                            $( 'h1.taxonomy' ).closest('.mce-container').hide().css('position', 'static');
                            $( '.mce-taxonomy' ).closest('.mce-container').hide().css('position', 'static');

                            return;
                        }

                        if( 'bbd-terms' == new_value ) {

                            $( 'h1.post-type' ).closest('.mce-container').hide().css('position', 'static');
                            $( '.mce-post-type' ).closest('.mce-container').hide().css('position', 'static');

                            $( 'h1.taxonomy' ).closest('.mce-container').show().css('position', 'absolute');
                            $( '.mce-taxonomy' ).closest('.mce-container').show().css('position', 'absolute');

                        }
                    },
                },

            ], // end: body

            /**
             * Callback to invoke when the form is submitted
             *
             * Inserts the shortcode built by the user into the post content
             *
             * @param   e       The event triggered on submission
             *
            onsubmit: function( e ) {
                editor.insertContent( '[' + e.data.shortcode + ']' );
            }

        }; // end: openArgs

        /**
         * Add additional items to the openArgs variable, which may depend on looping through 
         * data passed by PHP
         *

        // add the post type header and checkboxes
        openArgs.body.push( {
            type: 'container',
            html: '<h1 class="post-type" style="font-size: 1.1em; font-weight: bold;">Post Types</h1>',
        });

        $( post_types ).each( function() {
            openArgs.body.push( {
                type: 'checkbox',
                label: this.label,
                value: this.handle,
                classes: 'post-type',
            });
        });

        // add the taxonomy header and checkboxes
        openArgs.body.push( {
            type: 'container',
            html: '<h1 class="taxonomy" style="font-size: 1.1em; font-weight: bold; margin-top: 0.5em;">Taxonomies</h1>',
        });

        $( taxonomies ).each( function() {
            openArgs.body.push( {
                type: 'checkbox',
                label: this.label,
                value: this.handle,
                classes: 'taxonomy'
            });
        });

        editor.windowManager.open( openArgs );

    }); // end: Onclick for main Directory shortcode button

}); // end: document ready
*/