# Big Boom Directory

Directory management plugin for WordPress, based on Custom Post Types, Taxonomies, and Fields

## Features

* Create and manage custom post types and taxonomies from WP Admin.  Post type settings include:

    * Post ordering
    * Post type labels
    * WP Admin menu icon and position
    * URL slug and post type name/handle
    * Basic settings like 'Public' and 'Has Archive', 'Supports', and 'Exclude From Search'
    * Use hooks to add your own post type settings

* Add content using the WYSIWYG to act as the post type description for archive pages

* Pick and choose fields from Advanced Custom Fields groups to be displayed on single and archive views.  Compatible with both ACF and ACF Pro.

* Choose image size and alignment for single and archive views with ACF image fields

* Automatically detect URL and social media fields, converting them into links

* Support for ACF and ACF Pro field types like checkboxes, image, date picker, gallery field (with integration using Lightbox), Google Map, oEmbed

* Full-featured advanced search widget with customizable filters and field selection for the search results display

* Use hooks to further customize core functionality like post type editing and registration, front end field display and value processing, and more

* The following add-ons are available and/or currently under development:

    * Map
    * Import/Export
    * Posts Widget
    * Link Preview

---

## Notes

* Default behaviors for front end views generally fall in line with theme defaults. 

* Default field display on the front end is not well-defined without the Advanced Custom Fields plugin.  Using ACF adds support for field placement and ordering on single/archive pages for the various post types.  It also gives the ability to choose a field type for each field, which is not defined for any fields otherwise.

* Without ACF, one could still take advantage of plugin hooks that are already set to fire on directory views only, and use this as a starting point for manually inserting post meta fields for the different view types.

* By default, using a field a key equal `web`, `website`, or `url`; OR a key containing `_website` or `_url` will cause the field value to autolink on front end BBD views. The link texts are editable from the corresponding post type edit screen.  This setting may be deactivated.

* By default, when using a field key like facebook, twitter, youtube, googleplus, pinterest, instagram, or linkedin, the plugin matches various versions of these field names to try and convert them into social icons.  It's best to group all of these fields together when utilizing this feature.  This setting may be deactivated.

* Any time you update the options on the main plugin settings screen, the new values will be used as the default when creating a new post type.

* Uses the WP Object Cache for storing data about your post types created with the plugin.  See `Directory` > `Cache` for available options.

---

## Dependencies

* Uses [Extended Custom Post Types](https://github.com/johnbillion/extended-cpts) and [Extended Taxonomies](https://github.com/johnbillion/extended-taxos) for registering post types and taxonomies.  Plugin hooks provide access to everything being registered.

* Uses [CMB2](https://github.com/WebDevStudios/CMB2) for handling post meta boxes on the backend.  Plugin hooks provide access to the meta boxes used in editing post types and taxonomies.

---

## Shortcodes

### [bbd-a-z-listing]

 * Displays an A-Z listing of all posts for one or more custom post types

 * **Attributes:** 

    * `post_types` The post types to be displayed, separated by comma (_ex: 'book, movie'_)

    * `list_style` The list style for the `li` HTML elements (_ex: 'disc'_)

---

### [bbd-terms]

* Displays a term list for one or more taxonomies

* **Attributes:**

    * `taxonomies`  The taxonomies to show terms for, separated by comma `ex: 'book_genre, movie_genre'`. If no matching taxonomies are found, terms for all taxonomies are shown.

    * `list_style` The list style for the `li` HTML elements `ex: 'none'`

---

### [bbd-search]

* Displays an existing BBD Search widget 

* **Attributes:**

    * `widget_id`   (required) The widget ID number to be displayed (can be an inactive widget)

---

## Filters

### ````bbd_register_pt````

Use this filter to access the post type data before BBD post types are registered.  The filtered object is an array containing the arguments for [register\_extended\_post\_type](https://github.com/johnbillion/extended-cpts/wiki/Basic-usage), which is a wrapper for [register\_post\_type](https://codex.wordpress.org/Function_Reference/register_post_type).


#### Parameters
  
    $args = array(
    	'post_type' => (string),
    	'args' => (array, optional), 	// The arguments for `register_post_type`
    	'names' => (array, optional) 	// The corresponding parameter for `register_extended_post_type`
    )

  
#### Example

    add_filter( 'bbd_register_pt', 'my_register_pt' );
    function my_register_pt( $args ) {
    
        if( $args['post_type'] == 'my_post_type' ) {
            $args['args']['labels']['menu_name'] = 'Custom Menu Name';
        }
    	
        return $args;
    }

---

### ````bbd_register_tax````

Similar to `bbd_register_pt`, but for taxonomies.

---

### ````bbd_the_content````

Use this filter to modify the post content for BBD single post views.  Does not fire for non-BBD page views.

#### Parameters

    (string) $content: The post content along with any appended fields for the current view

#### Return
    
    (string) You must return the altered HTML to be displayed

#### Example

Below, we're appending an additional field called `phone` below the default fields and post content.

    add_filter( 'bbd_the_content', 'my_the_content' );
    function my_the_content( $content ) {

        global $post;

        $phone = get_post_meta( $post->ID, 'phone', true );
        if( $phone ) $new_html = '<p>Phone: '. $phone .'</p>':

        return $content . $new_html;
    }

---

### ````bbd_the_excerpt````

Use this filter to modify the post content for BBD archive views.  Does not fire for non-BBD page views.  Similar to
`bbd_the_content`, except it fires for archive views instead of single post views.

---

### ````bbd_make_excerpt````

Unlike the `bbd_the_content` and `bbd_the_excerpt`, which are essentially wrappers for `the_content` and `the_excerpt` that fire only on the built-in WP single and archive views, the `bbd_make_excerpt` filter fires whenever the plugin is generating its own custom view (like search results from the search widget).  You can use this filter to alter the excerpts shown for the posts in these cases.

#### Parameters

    (string) $excerpt: The auto-generated excerpt 

#### Return

    (string) You must return the new excerpt that you wish to be displayed

#### Examples

    # This example strips all HTML tags from the excerpt
    add_filter( 'bbd_make_excerpt', 'my_make_excerpt' );
    function my_make_excerpt( $excerpt ) {
        return strip_tags( $excerpt );
    }

    # This example gets rid of the excerpt altogether
    add_filter( 'bbd_make_excerpt', 'my_make_excerpt' );
    function my_make_excerpt( $excerpt ) {
        return '';
    }

---

### ````bbd_field_value````

Use this to filter values as they are retrieved by the plugin.  Note that this filter applies to all fields, while the `bbd_field_value_{$field_name}` filter can be used for more specific targeting.  Sequentially, this filter 
always fires before `bbd_field_value_{$field_name}`.

#### Parameters

    (string)    $value     The field value before filtering
    (BBD_Field) $field     The field object whose value we are getting
    (int)       $post_id   The post ID we are getting the value for

#### Return

    (string) You must return the altered field value

---

### ````bbd_field_value_{$field_name}````

Use this to filter a field value before it is displayed on the front end. Use your own field name (meta key) in place of `{$field_name}`.  Note that the filter fires whether or not the field has a value, as long as the field is selected for this view.

#### Parameters

    (string)    $value:     The field value to be displayed
    (BBD_Field) $field:     The field object that is being displayed
    (int)       $post_id    The post ID we are getting the value for



#### Return

    (string) You must return the altered field value

#### Example

Below, we are filtering the value of a field called `email` and adding a mailto link.

    add_filter( 'bbd_field_value_email', 'my_email_value_filter' );
    function my_email_value_filter( $value ){

        $value = "<a href='mailto:". $value . "' >" . $value . "</a>";
        return $value;

    }

---

### ````bbd_field_label_{$field_name}````

Use this filter to edit a filed's label and label wrap before it is displayed on the front end. Use your own field name (meta key) in place of `{$field_name}`. Note that the filter fires whether or not the field has a value, as long as the field is selected for this view.

#### Parameters

    (array) $label {
        'text'   => (string)   // The label text
        'before' => (string)   // HTML that comes before the label (Default: "<label>")
        'after'  => (string)   // HTML that comes after the label (Default: ": &nbsp; </label>")
    }

    (BBD_Field) $field: The field object that is being displayed


#### Return

    (array)  You must return the altered $label array

#### Example

Below, we are setting the `first_name` field's label text to "First" and the `last_name` field's label text to "Last"

    add_filter( 'bbd_field_label_first_name', 'my_first_name_label' );
    function my_first_name_label( $label ) {
        $label['text'] = 'First';
        return $label;
    }

    add_filter( 'bbd_field_label_last_name', 'my_last_name_label' );
    function my_last_name_label( $label ) {
        $label['text'] = 'Last';
        return $label;
    }

---

### ````bbd_field_wrap_{$field_name}````

Similar to `bbd_field_label_{$field_name}`, except we are altering the entire wrapping element for a field.  This can be particularly useful whenever two fields need to share the same parent wrapper, or when a particular class or id needs to be added to the HTML.

#### Parameters

    (array) $wrap {
        'before_tag'    => 'div',
        'after_tag'     => 'div',
        'classes'       => (array),
        'id'            => (string),
    }

    (BBD_Field) $field: The field being displayed

#### Return

    (array) You must return the $wrap array

#### Example

Below is a fairly involved example that uses the `bbd_field_wrap` filter along with `bbd_field_value` and `bbd_field_label` to do the following:

* Append a space to the `first_name` field
* Remove individual labels for the `first_name` and `last_name` fields
* Remove the individual wrappers for the `first_name` and `last_name` fields and wrap them together in a single div

First, append a space to the first name

    add_filter( 'bbd_field_value_first_name', 'my_first_name_value' );
    function my_first_name_value( $value ) {
        return $value . ' ';
    }

Then, empty out the labels for first and last name fields

    add_filter( 'bbd_field_label_first_name', 'my_name_label' );
    add_filter( 'bbd_field_label_last_name', 'my_name_label' );
    function my_name_label( $label ) {
        return array('before' => '', 'after' => '', 'text' => '');
    }

Finally, add a `<p>` wrapper around the first and last name fields 

    add_filter( 'bbd_field_wrap_first_name', 'my_name_wrap', 10, 2 );
    add_filter( 'bbd_field_wrap_last_name', 'my_name_wrap', 10, 2 );
    function my_name_wrap( $wrap, $field ) {

        // for the first name field
        if( 'first_name' == $field->key ) {
            $wrap['before_tag'] = 'p';
            $wrap['after_tag'] = '';
            $wrap['classes'][] = 'name-field';
            $wrap['id'] = 'my-name-field';
        }

        // for the last name field
        elseif( 'last_name' == $field->key ) {
            $wrap['before_tag'] = '';
            $wrap['after_tag'] = 'p';

        }
        
        return $wrap;
    }
 
---

### ````bbd_link_text````

Use to change the default "View Website" link text for auto detected URL fields (`web`, `website`, `url`, or matching `_website` or `_url`)

#### Parameters

    (string)        $text      The link text currently set for display (default: "View Website")
    (BBD_Field)    $field    The field object currently being displayed

#### Return

    (string) You must return the new link text for display

#### Examples

Below, we're changing the link text to 'Visit Webpage'

    add_filter( 'bbd_link_text', 'my_link_text' );
    function my_link_text() {
        return 'Visit Webpage';
    }


For a more complex example, we can get the post currently being displayed and incorporate a custom field with the link text.  In this example, for an author named John we would have the link text "View John's Website". Note that we are checking first that the post has a specific post type, `author`.  We also check that the user has a value for the custom field `first_name`.

    add_filter( 'bbd_link_text', 'my_variable_link_text', 10, 2 );
    function my_variable_link_text( $text, $field ) {
        
        # get the current post in the loop
        global $post;

        # make sure the post type is `author`
        if( 'author' != $post->post_type ) return $text;

        # get the user's first name if it exists
        if( $first_name = get_post_meta( $post->ID, 'first_name', true ) ) {

            # add first name to link text
            return "Visit {$first_name}'s Website";
        }

        # otherwise, return a generic link text
        return 'Visit Website';
    }

---

### ````bbd_pt_description_wrap````

This hook allows the customization of the containers for the post type descriptions that show on each post type archive page.  The post type description can be created by using the post content area for any post type.

Also, see the actions `bbd_before_pt_description` and `bbd_after_pt_description` which give more general control that may be needed for complex post type descriptions.

We assume that the description should have the same HTML structure as a single loop item, in order to integrate best with the theme being used.  Since the loop item HTML will be different for each theme, we take the approach of displaying a more-or-less unstyled description above the loop. This hook is then provided to allow the wrapper to be altered to match the theme.


#### Parameters

    (array) $wrap {
        'before_tag'    => 'div',
        'after_tag'     => 'div',
        'classes'       => (array),
        'id'            => (string),
    }

#### Return

    (array)     You must return the altered $wrap array

---

### ````bbd_term_description_wrap````

This filter is similar to the `bbd_pt_description_wrap`, except it applies to term archive pages instead of post type archive pages.  See above for examples, as it works just the same if you substitute `term` for `pt`.

Unlike the descriptions for post type archives, term archive descriptions are something that many themes already utilize.  For this reason, we do not place term descriptions on term archive pages by default.  You must check the `Show term descriptions on term archive pages` checkbox when creating or editing your taxonomy on the backend.

---

### ````bbd_search_widget_query_args````

Alters the arguments for the WP_Query instance used to get the search results when using the Search Widget

#### Parameters

    $query_args (array)                 The query args as processed by the plugin, based on form input
    $widget     (BBD_Search_Widget)     The instance of the search widget which was submitted

#### Return

    (array)     You must return the altered $query_args array

---

### ````bbd_search_widget_query````

Alters the query response object for search widget results, after getting posts

#### Parameters

    $query      (WP_Query)              The WP_Query object for the search results
    $widget     (BBD_Search_Widget)     The instance of the search widget which was submitted

#### Return

    (WP_Query)      You must return the altered WP_Query object

---

## Actions

### ````bbd_pre_get_posts````

Use this action to alter the global `$wp_query` for BBD views.  It's essentially the same as `pre_get_posts`, except BBD defaults are in place.  Also, there is no need to check whether we're viewing a page for a BBD object or whether the query is the main query object, as this has been verified before the filter fires.  

Below are the default query arguments for a BBD view:

* `order_by`: post_title
* `order`: ASC

#### Parameters

    $query: The same value passed by WP's `pre_get_posts` action, with the BBD defaults in place

#### Example

Below, we are using the `bbd_pre_get_posts` filter to order BBD posts by a field called `last_name`

    add_action( 'bbd_pre_get_posts', 'my_pre_get_posts' );
    function my_pre_get_posts( $query ) {

        $query->query_vars['orderby'] = 'meta_value';
        $query->query_vars['meta_key'] = 'last_name';
    }

---

### ````bbd_wp````

Fires on the `wp` hook, but only for BBD views

### ````bbd_enqueue_scripts````

Fires on the `wp_enqueue_scripts` hook, but only for BBD views

---

### ````bbd_before_fields_wrap````
### ````bbd_after_fields_wrap````

Use these to insert content before or after the fields wrap container.  Note that the hooks fire on both archive pages and single pages.  

The following example adds content before the fields wrap container and illustrates how the global `$bbd_view` object can be used to differentiate between archive and single views

    add_action( 'bbd_before_fields_wrap', 'my_before_fields_wrap' );
    function my_before_fields_wrap() {
    
        global $bbd_view;
        if( 'archive' == $bbd_view->view_type ) {

            // stuff to do on archive listings
        }
        elseif( 'single' == $bbd_view->view_type ) {

            // stuff to do on single listings
        }
    }

---

### ````bbd_pre_render_field_{$field_name}````
### ````bbd_post_render_field_{$field_name}````

These actions allow users to insert their own HTML before (*pre*) or after (*post*) a field is rendered.  Use your own field name (meta key) in place of `{$field_name}`.  Note that the filter fires whether or not the field has a value, as long as the field is selected for this view.

### Parameters

````$field```` (BBD_Field) The field object being displayed

### Example

Below is an example to wrap a field called `email` in a div. Note the example doesn't use the `$field` object, although it is available inside the function.

    add_action('bbd_pre_render_field_email', 'my_pre_email');
    add_action('bbd_post_render_field_email', 'my_post_email');

    function my_pre_email( $field ){
    ?>
        <div id='my-email-field' class='my-custom-class' >
    <?php
    }

    function my_post_email( $field ){
    ?>
        </div>
    <?php
    }


---

### ````bbd_before_pt_description````
### ````bbd_after_pt_description````

These actions are used to insert HTML (or perform other tasks) before and after the post type description for post type archive pages. The post type description can be created by using the post content area for any post type.

As mentioned above for the `bbd_pt_description_wrap` filter, these actions are mainly intended to let users match the post type description to their specific theme.

These filters do not fire for post types whose description is empty.

#### Example

This example gives the post type description the same layout as a single loop item for the Twentyfifteen theme.

    add_action( 'bbd_before_pt_description', 'my_before_description' );
    function my_before_description() {
    ?>
        <article class='hentry'><div class='entry-content'>
    <?php
    }
    
    add_action( 'bbd_after_pt_description', 'my_after_description' );
    function my_after_description() {
    ?>
        </div></article>
    <?php
    }

---

### ````bbd_before_term_description````
### ````bbd_after_term_description````

These action hooks are similar to `bbd_before_pt_description` and `bbd_after_pt_description`, in that they are intended to let users match the descriptions on term archive pages to their particular theme.  See above for examples, as they work just the same if you substitute `term` for `pt`.

Unlike the descriptions for post type archives, term archive descriptions are something that many themes already utilize.  For this reason, we do not place term descriptions on term archive pages by default.  You must check the `Show term descriptions on term archive pages` checkbox when creating or editing your taxonomy on the backend.

---

### ````bbd_before_search_widget_container````
### ````bbd_after_search_widget_container````

Allow insertion of content before/after the main search widget container

#### Parameters

````$widget````     (BBD_Search_Widget)     The current widget being rendered (use $widget->instance to get the instance)

---

### ````bbd_before_search_filter````
### ````bbd_after_search_filter````

Allow insertion of content before/after search filters inside the search widget

#### Parameters

````$setting````    (array)                 The current search filter whose HTML is being rendered
````$widget````     (BBD_Search_Widget)     The current widget being rendered (use $widget->instance) to get the instance

---

### ````bbd_before_search_result````
### ````bbd_after_search_result````

These actions allow users to insert content before or after the search results rendered by the search widget. Note that the widget uses the post excerpt if defined, or truncates the post content to the specified length otherwise.

#### Parameters

````$post_id```` (int)                  The post ID for the current search result being displayed
````$widget````  (BBD_Search_Widget)    The widget instance that was submitted

#### Example

The following example will display a field called `email` before the result's excerpt and then show a list of term links for the post from a taxonomy called `movie_genre` after the excerpt

    add_action( 'bbd_before_search_result', 'my_bbd_before_search_result', 10, 2 );
    function my_bbd_before_search_result( $post_id, $widget ) {
        bbd_field( $post_id, 'email' );
    }
    
    add_action( 'bbd_after_search_result', 'my_bbd_after_search_result', 10, 2 );
    function my_bbd_after_search_result( $post_id, $widget ) {
    
        $terms = wp_get_post_terms( $post_id, 'movie_genres' );
        $term_links = array();
    
        foreach( $terms as $term ) {
            $term_links[] = '<a href="' . get_term_link( $term ) . '">' . $term->name . '</a>';
        }

        if( ! empty( $term_links ) ) {
            echo '<p>Located in: ' . implode( ', ', $term_links ) . '</p>';
        }
    }

---

### ````bbd_field_type_details````

This action fires after the field type radio buttons for each field filter within the search widget. Use it to insert additional field types, and then use `bbd_search_widget_query_args` to edit the WP_Query as needed for the search results page

#### Parameters

    $widget (BBD_Search_Wiget)      An instance of the search widget object
    $field  (BBD_Field)             The field object that we are adding a type for

---

### ````bbd_after_search_widget_form````

Perform an action (like executing an inline JS function) after the search widget form is rendered.  This helps with initializing parts of the widget after saving without doing a hard screen refresh

---

### CMB2 Actions

Use these actions to alter the respective CMB2 meta box objects on the post type edit screen.  

### ````bbd_cmb2_post_type_settings````
### ````bbd_cmb2_advanced_post_type_settings````
### ````bbd_cmb2_post_type_fields_select````
### ````bbd_cmb2_post_type_advanced_fields_setup````

### ````bbd_cmb2_taxonomy_settings````
### ````bbd_cmb2_advanced_taxonomy_settings````

If adding a new field called `my_field` to a meta box, apply the `$prefix` parameter to the field's `id` value, and then access the field value via `$post_type->my_field`, where `$post_type` is a `new BBD_PT( $post_id )`.  

If the prefix is not applied, then `my_field` would be stored as a single post meta field for the post type being edited.  You could then access this as usual via `get_post_meta( $post_id, 'my_field', true )`.

#### Parameters

    (CMB2 object)   $meta_box        The CMB2 meta box object (depending on which of the above hooks is in use)
    (string)        $prefix          The prefix currently used by the meta box fields (most likely `_bbd_meta_`)

#### Example

In the example below, we're adding a field called `my_field` to the "Advanced Post Type Settings" meta box

    add_action( 'bbd_cmb2_advanced_post_type_settings', 'my_cmb2_advanced_post_type_settings', 10, 2 );
    function my_cmb2_advanced_post_type_settings( $meta_box, $prefix ) {

        $meta_box->add_field( array(
            'name'  => 'My post type field',
            'id'    => $prefix . 'my_field',
            'type'  => 'select',
            'options' => array(
                'one'      => 'One',
                'two'      => 'Two',
                'three'     => 'Three',
            ),
            'default'   => 'one',
        ));
    }

---

## Functions

Here are some helper functions that you can call within your child theme

### ````is_bbd_view()````

Returns `true` if we are viewing a BBD object (single post, post archive, or term archive) and `false` otherwise.

---

### ````bbd_get_field_value( mixed, mixed )````

Returns an end-usable field value, applying the plugin's filters and handlers for ACF field types.  

The function inputs can be either a single string if we're currently in the loop (similar to [`get_field`](http://www.advancedcustomfields.com/resources/get_field)), or a post ID and string (similar to [`get_post_meta`](https://developer.wordpress.org/reference/functions/get_post_meta)).

---

### ````bbd_field( $post_id, $field_key )````

Renders HTML for a single field for the given post ID.  Any custom hooks registered for the field wrap, label, or value will be executed.

---

### ````bbd_get_field_html( $post_id, $field_key )````

Returns an HTML string for a single field for the given post ID.  Any custom hooks registered for the field wrap, label, or value will fire.