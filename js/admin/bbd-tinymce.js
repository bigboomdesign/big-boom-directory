/**
 * Inserts the Directory shortcode modal HTML into the document
 * Handles the response when the "Directory" button above the TinyMCE is clicked
 * Handles interaction events for selecting shortcode parameters within the modal
 * Handles the response when shortcode parameters are submitted via the modal box
 *
 * Makes the main bbdShortcodeBuilder object available in the global scope, so that add-ons
 * can access it and add their own event handlers for shortcodes they may add.
 *
 * @since   2.2.0
 */
var bbdShortcodeBuilder = (function( $ ) {

    /**
     * Data passed via wp_localize_script
     */
     var postTypes = BBD_Shortcode_Data.post_types,
        taxonomies = BBD_Shortcode_Data.taxonomies,
        shortcodes = BBD_Shortcode_Data.shortcodes,
        widgetIds = BBD_Shortcode_Data.widget_ids;

    /**
     * Main shortcode builder object
     */
    var shortcodeBuilder = {

        // the modal element
        modal: document.createElement( 'div' ),

        // the form element used to build the shortcode
        form: document.createElement( 'form' ),

        /**
         * Initialize the object
         */
        init: function() {
            this.initModal();
        }, // end: init()

        /**
         * Initialize parts of the object that depend on the document being loaded
         */
        ready: function() {

            // append the modal to the document body
            document.body.appendChild( this.modal );

            // initialize jQuery objects for key elements
            this.$button = $( 'button#insert-bbd-shortcode' );
            this.$modal = $( this.modal );
            this.$form = $( this.form );

            // create and append the modal content to the modal
            this.addModalContent();

            // bind events for the shortcode builder
            this.bindEvents();

            // make sure conditional logic is initialized in the modal
            this.toggleShortcode();

        }, // end: ready()

        /**
         * Initialize the modal element
         */
        initModal: function() {
            this.modal.className = 'bbd-shortcode-modal-wrap';
            this.modal.innerHTML = '<div class="bbd-shortcode-modal-inner"><button id="modal-close" type="button" class="button-link media-modal-close"><span class="media-modal-icon"><span class="screen-reader-text">Close shortcode builder panel</span></span></button></div>';
        },

        /**
         * Create the modal content and append to the modal element
         */
        addModalContent: function() {

            // placeholder reference that we can use within functions with different scope
            var $form = this.$form;

            this.form.id = 'bbd-shortcode';
            this.form.innerHTML = '<h1>Big Boom Directory Shortcode</h1>';

            /**
             * Add elements to the modal form
             */

            // "Select Shortcode" container
            this.$form.$shortcodes = $( '<div id="shortcodes"><h2>Select Shortcode</h2></div>' );
            this.$form.append( this.$form.$shortcodes );

            // add shortcode <select>
            this.$shortcodeToggle = $( '<select id="shortcode" name="shortcode"></select>' );
            this.$form.$shortcodes.append( this.$shortcodeToggle );

            // add shortcode <option> elements
            $( shortcodes ).each( function() {
                $form.$shortcodes.find( 'select' ).append( '<option value="' + this.name + '" >' + this.label + '</option>' );
            });


            // "Select Post Types" container
            this.$form.$postTypes = $( '<div id="post-types"><h2>Select Post Types</h2></div>' );
            this.$form.append( this.$form.$postTypes );

            // add post type checkboxes
            $( postTypes ).each( function() {
                $form.$postTypes.append( '<label><input type="checkbox" name="post_types[]" value="' + this.handle + '" /> ' + this.label + '</label>' );
            });

            // "Select Taxonomies" container
            this.$form.$taxonomies = $( '<div id="taxonomies"><h2>Select Taxonomies</h2></div>' );
            this.$form.append( this.$form.$taxonomies );

            // add taxonomy checkboxes
            $( taxonomies ).each( function() {
                $form.$taxonomies.append( '<label><input type="checkbox" name="taxonomies[]" value="' + this.handle + '" /> ' + this.label + '</label>' );
            });

            // "Select list style type" container
            this.$form.$listStyles = $( '<div id="list-styles"><h2>Select List Style Type</h2></div>' );
            this.$form.append( this.$form.$listStyles );

            // add list style <select>
            this.$form.$listStyles.append( '<select id="list-style" name="list-style"></select>' );

            // add list style <option> elements
            $( [ 'inherit', 'none', 'disc', 'circle', 'square' ] ).each( function() {
                $form.$listStyles.find('select').append( '<option value="' + this + '">' + this.charAt(0).toUpperCase() + this.slice(1) + '</option>' );
            });

            // "Select Search Widget" container
            this.$form.$searchWidgets = $( '<div id="search-widgets"><h2>Select Search Widget</h2></div>' );
            this.$form.append( this.$form.$searchWidgets );

            // add search widget <select>
            this.$form.$searchWidgets.append( '<select id="search-widget-id" name="search_widget_id"></select>' );
            
            // add search widget <option> elements
            $( widgetIds ).each( function() {
                $form.$searchWidgets.find( 'select' ).append( '<option value="' + this.id + '">' + 
                    this.id + ': ' + this.title + ' ( ' + this.description + ' )' + '</option>' 
                );
            });

            // cancel button
            this.$form.$cancel = $( '<button type="button" id="bbd-shortcode-cancel" class="button button-secondary">Cancel</button>' );
            this.$form.append( this.$form.$cancel );

            // submit button
            this.$form.append( '<button type="submit" class="button button-primary">Submit</button>' );

            // append form HTML to modal
            this.$modal.find('.bbd-shortcode-modal-inner').append( this.form );

        }, // end: addModalContent()

        /**
         * Close the modal box
         */
        close: function() {

            // close the modal
            this.$modal.removeClass('active');

        },

        /**
         * Insert a given string into the TinyMCE content area
         *
         * @param   string      content     The string to insert
         */
        insertTinyContent: function( content ) {

            // does the TinyMCE content area have an active cursor
            var isTinyActive = tinymce.activeEditor && 'content' == tinymce.activeEditor.id;

            // insert the shortcode into the editor
            if( isTinyActive ) {
                tinymce.activeEditor.execCommand('mceInsertContent', false, content );
            }
            else {
                tinymce.get( 'content' ).execCommand( 'mceInsertContent', false, content );
            }
        },

        /**
         * Bind event listeners to shortcode builder elements
         */
        bindEvents: function() {

            // onclick for main Directory shortcode button
            this.$button.on('click', function(event){
                event.preventDefault();
                $('.bbd-shortcode-modal-wrap').addClass('active');
            });

            // onclick for the modal close button
            this.$modal.find('#modal-close').on('click', function(event){
                $('.bbd-shortcode-modal-wrap').removeClass('active');
            });
            
            // onchange for main shortcode <select> element
            this.$shortcodeToggle.on( 'change', function() {
                shortcodeBuilder.toggleShortcode();
            });

            // onclick for the "Cancel" button
            this.$form.find( '#bbd-shortcode-cancel' ).on( 'click', function() {
                shortcodeBuilder.$modal.removeClass( 'active' );
            });

            // on submit for the shortcode builder form
            this.$form.on('submit', function(event){

                event.preventDefault();

                // which shortcode the user selected
                var shortcode = shortcodeBuilder.$shortcodeToggle.val();

                /**
                 * Gather the shortcode parameters submitted by the user
                 */

                // post types
                var selectedPostTypes = [];
                shortcodeBuilder.$form.$postTypes.find('input').each( function() {
                    if( true == $( this ).prop('checked') ) {
                        selectedPostTypes.push( this.value );
                    }
                } );

                // taxonomies
                var selectedTaxonomies = [];
                shortcodeBuilder.$form.$taxonomies.find( 'input' ).each( function() {
                    if( true == $( this ).prop('checked') ) {
                        selectedTaxonomies.push( this.value );
                    }
                });

                // search widget
                var selectedSearchWidget = shortcodeBuilder.$form.$searchWidgets.find( 'select' ).val();

                // list style
                var selectedListStyle = shortcodeBuilder.$form.$listStyles.find('select').val();

                /**
                 * Compile the selected shortcode and parameters into a string that we'll insert
                 * into the content area
                 */
                var shortcodeContent = '';

                // if inserting [bbd-search]
                if( 'bbd-search' == shortcode ) {

                    // make sure we have a widget selected
                    if( ! selectedSearchWidget ) {
                        shortcodeBuilder.addError( shortcodeBuilder.$form.$searchWidgets );
                        return;
                    }

                    else {
                        shortcodeBuilder.removeError( shortcodeBuilder.$form.$searchWidgets );
                    }

                    // build the shortcode string
                    shortcodeContent = '[bbd-search widget_id="' + selectedSearchWidget + '"]';

                } // end if: [bbd-search]

                // if inserting [bbd-terms]
                if( 'bbd-terms' == shortcode ) {

                    // make sure we have taxonomies selected
                    if( selectedTaxonomies.length == 0 ) {
                        shortcodeBuilder.addError( shortcodeBuilder.$form.$taxonomies );
                        return;
                    }

                    else {
                        shortcodeBuilder.removeError( shortcodeBuilder.$form.$taxonomies );
                    }

                    // build the shortcode string
                    shortcodeContent = '[bbd-terms taxonomies="' + selectedTaxonomies.join( ', ' ) + '" ';
                        if( selectedListStyle && 'inherit' != selectedListStyle ) {
                            shortcodeContent += 'list_style="' + selectedListStyle + '" ';
                        }
                    shortcodeContent += ']';

                } // end if: [bbd-terms]

                // if inserting [bbd-a-z-listing]
                if( 'bbd-a-z-listing' == shortcode ) {

                    // make sure we have post types selected
                    if( selectedPostTypes.length == 0 ) {
                        shortcodeBuilder.addError( shortcodeBuilder.$form.$postTypes );
                        return;
                    }

                    else {
                        shortcodeBuilder.removeError( shortcodeBuilder.$form.$postTypes );
                    }

                    // build the shortcode string
                    shortcodeContent = '[bbd-a-z-listing post_types="' + selectedPostTypes.join( ', ' ) + '" ';
                        if( selectedListStyle && 'inherit' != selectedListStyle ) {
                            shortcodeContent += 'list_style="' + selectedListStyle + '" ';
                        }
                    shortcodeContent += ']';

                } // end if: [bbd-a-z-listing]

                /**
                 * Complete the handling of the event by closing the modal and inserting 
                 * the shortcode
                 *
                 * Note that we will have executed a `return` above if any errors were present
                 */

                // close the modal
                shortcodeBuilder.close();

                // insert the content
                if( '' !== shortcodeContent ) {
                    shortcodeBuilder.insertTinyContent( shortcodeContent );
                }

            }); // end: on submit this.$modal

        }, // end: bindEvents()

        toggleShortcode: function() {

            var value = this.$shortcodeToggle.val();

            var divs = [ this.$form.$postTypes, this.$form.$taxonomies, this.$form.$listStyles, this.$form.$searchWidgets ];
            $( divs ).each( function() {
                this.hide();
            });

            // If selecting Search Widget
            if( 'bbd-search' == value ) {
                this.$form.$searchWidgets.show();
            }

            // If selecting A-Z Listing
            if( 'bbd-a-z-listing' == value ) {
                this.$form.$postTypes.show();
                this.$form.$listStyles.show();
            }

            // If selecting Terms List
            if( 'bbd-terms' == value ) {
                this.$form.$taxonomies.show();
                this.$form.$listStyles.show();
            }

        }, // end: toggleShortcode()

        addError: function( $elem ) {

            $elem.addClass( 'error' );
            if( $elem.find( 'p.error-message' ).length == 0 ) {
                $elem.prepend( '<p class="error-message">This is a required field</p>' );
            }

        },

        removeError: function( $elem ) {

            $elem.removeClass( 'error' );
            $elem.find('p.error-message').remove();
        }

    }; // end: shortcodeBuilder

    // initialize the shortcode builder object
    shortcodeBuilder.init();

    // document ready callback for shortcode builder object
    $(document).ready(function() {
        shortcodeBuilder.ready();
    });

    return shortcodeBuilder;

})( jQuery );