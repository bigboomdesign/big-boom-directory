/**
 * Inserts the Directory button and the modal HTML into the document
 * Handles the response when the "Directory" button above the TinyMCE is clicked
 * Handles interaction events for selecting shortcode parameters within the modal
 * Handles the response when shortcode parameters are submitted via the modal box
 *
 * @since   2.2.0
 */
(function( $ ) {

    var iconUrl = BBD_Shortcode_Data.icon_url,
        postTypes = BBD_Shortcode_Data.post_types,
        taxonomies = BBD_Shortcode_Data.taxonomies,
        shortcodes = BBD_Shortcode_Data.shortcodes,
        widgetIds = BBD_Shortcode_Data.widget_ids;

    var shortcodeBuilder = {

        button: document.createElement( 'button' ),
        icon: document.createElement( 'span' ),
        modal: document.createElement( 'div' ),
        form: document.createElement( 'form' ),
        $shortcodeToggle: '',

        init: function() {

            this.initButton();
            this.initModal();

        }, // end: init()

        ready: function() {

            var buttonParent = document.getElementById('wp-content-media-buttons');
            buttonParent.appendChild( this.button );

            document.body.appendChild( this.modal );

            this.$button = $( this.button );
            this.$modal = $( this.modal );
            this.$form = $( this.form );

            this.$modal.find('#modal-close').on('click', function(event){
                $('.bbd-shortcode-modal-wrap').removeClass('active');
            });

            this.addModalContent();
            this.bindEvents();

        }, // end: ready()

        initButton: function() {

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
        },

        initModal: function() {
            this.modal.className = 'bbd-shortcode-modal-wrap';
            this.modal.innerHTML = '<div class="bbd-shortcode-modal-inner"><button id="modal-close" type="button" class="button-link media-modal-close"><span class="media-modal-icon"><span class="screen-reader-text">Close shortcode builder panel</span></span></button></div>';
        },

        addModalContent: function() {

            var $form = this.$form;

            this.form.id = 'bbd-shortcode';
            this.form.innerHTML = '<h1>Big Boom Directory Shortcode</h1>';

            // select shortcode
            this.$form.$shortcodes = $( '<div id="shortcodes"><h2>Select Shortcode</h2></div>' );
            this.$form.append( this.$form.$shortcodes );

            // add shortcode <select>
            this.$shortcodeToggle = $( '<select id="shortcode" name="shortcode"></select>' );
            this.$form.$shortcodes.append( this.$shortcodeToggle );

            // add shortcode <option> elements
            $( shortcodes ).each( function() {
                $form.$shortcodes.find( 'select' ).append( '<option value="' + this.name + '" >' + this.label + '</option>' );
            });


            // select post types
            this.$form.$postTypes = $( '<div id="post-types"><h2>Select Post Types</h2></div>' );
            this.$form.append( this.$form.$postTypes );

            $( postTypes ).each( function() {
                $form.$postTypes.append( '<label><input type="checkbox" name="post_types[]" value="' + this.handle + '" /> ' + this.label + '</label>' );
            });

            // select taxonomies
            this.$form.$taxonomies = $( '<div id="taxonomies"><h2>Select Taxonomies</h2></div>' );
            this.$form.append( this.$form.$taxonomies );

            $( taxonomies ).each( function() {
                $form.$taxonomies.append( '<label><input type="checkbox" name="taxonomies[]" value="' + this.handle + '" /> ' + this.label + '</label>' );
            });

            // select list style type
            this.$form.$listStyles = $( '<div id="list-styles"><h2>Select List Style Type</h2></div>' );
            this.$form.append( this.$form.$listStyles );

            this.$form.$listStyles.append( '<select id="list-style" name="list-style"></select>' );

            $( [ 'inherit', 'none', 'disc', 'circle', 'square' ] ).each( function() {
                $form.$listStyles.find('select').append( '<option value="' + this + '">' + this.charAt(0).toUpperCase() + this.slice(1) + '</option>' );
            });

            // select Search Widget
            this.$form.$searchWidgets = $( '<div id="search-widgets"><h2>Select Search Widget</h2></div>' );
            this.$form.append( this.$form.$searchWidgets );

            this.$form.$searchWidgets.append( '<select id="search-widget-id" name="search_widget_id"></select>' );
            $( widgetIds ).each( function() {
                $form.$searchWidgets.find( 'select' ).append( '<option value="' + this.id + '">' + 
                    this.id + ': ' + this.title + ' ( ' + this.description + ' )' + '</option>' 
                );
            });

            // cancel button
            this.$form.append( '<button type="button" id="bbd-shortcode-cancel" class="button button-secondary">Cancel</button>' );

            // submit button
            this.$form.append( '<button type="submit" class="button button-primary">Submit</button>' );

            // append form HTML to modal
            this.$modal.find('.bbd-shortcode-modal-inner').append( this.form );

        }, // end: addModalContent()

        bindEvents: function() {

            // onclick for main Directory shortcode button
            this.$button.on('click', function(event){
                event.preventDefault();
                $('.bbd-shortcode-modal-wrap').addClass('active');
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
                    
                    if( ! selectedSearchWidget ) {
                        shortcodeBuilder.addError( shortcodeBuilder.$form.$searchWidgets );
                        return;
                    }

                    else {
                        shortcodeBuilder.removeError( shortcodeBuilder.$form.$searchWidgets );
                    }

                    shortcodeContent = '[bbd-search widget_id="' + selectedSearchWidget + '"]';

                } // end if: [bbd-search]

                // if inserting [bbd-terms]
                if( 'bbd-terms' == shortcode ) {

                    if( selectedTaxonomies.length == 0 ) {
                        shortcodeBuilder.addError( shortcodeBuilder.$form.$taxonomies );
                        return;
                    }

                    else {
                        shortcodeBuilder.removeError( shortcodeBuilder.$form.$taxonomies );
                    }

                    shortcodeContent = '[bbd-terms taxonomies="' + selectedTaxonomies.join( ', ' ) + '" ';
                        if( selectedListStyle && 'inherit' != selectedListStyle ) {
                            shortcodeContent += 'list_style="' + selectedListStyle + '" ';
                        }
                    shortcodeContent += ']';

                } // end if: [bbd-terms]

                // if inserting [bbd-a-z-listing]
                if( 'bbd-a-z-listing' == shortcode ) {
                    
                    if( selectedPostTypes.length == 0 ) {
                        shortcodeBuilder.addError( shortcodeBuilder.$form.$postTypes );
                        return;
                    }

                    else {
                        shortcodeBuilder.removeError( shortcodeBuilder.$form.$postTypes );
                    }

                    shortcodeContent = '[bbd-a-z-listing post_types="' + selectedPostTypes.join( ', ' ) + '" ';
                        if( selectedListStyle && 'inherit' != selectedListStyle ) {
                            shortcodeContent += 'list_style="' + selectedListStyle + '" ';
                        }
                    shortcodeContent += ']';

                } // end if: [bbd-a-z-listing]

                // close the modal if we made it this far
                shortcodeBuilder.$modal.removeClass('active');

                var isTinyActive = tinymce.activeEditor && 'content' == tinymce.activeEditor.id;

                //var shortcodeContent = '[bbd-shortcode post_types="' + selectedPostTypes.join( ', ' ) + '"]';

                if( isTinyActive ) {
                    tinymce.activeEditor.execCommand('mceInsertContent', false, shortcodeContent );
                }
                else {
                    tinymce.get( 'content' ).execCommand( 'mceInsertContent', false, shortcodeContent );
                }

            }); // end: on submit this.$modal

            // onclick for the "Cancel" button
            this.$form.find( '#bbd-shortcode-cancel' ).on( 'click', function() {
                shortcodeBuilder.$modal.removeClass( 'active' );
            });
            
            // onchange for main shortcode select
            this.$shortcodeToggle.on( 'change', function() {
                shortcodeBuilder.toggleShortcode();
            });

            // make sure conditional logic is initialized in the modal on page load
            this.toggleShortcode();

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

        }, // end: highlightError

        removeError: function( $elem ) {

            $elem.removeClass( 'error' );
            $elem.find('p.error-message').remove();
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