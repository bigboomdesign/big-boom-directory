
# Custom Post Type Directory (Version 2)

## Run `git checkout master` for Version 1
---

Directory management system based on Custom Post Types, Taxonomies, and Fields

## Features

* Creates custom post type and taxonomies from backend without coding

---

## Notes

* Default behaviors for front end field display are not well-defined without Advanced Custom Fields plugin.  Using ACF adds support for field placement and ordering on single/archive pages for your various post types as well as field type selection.

* Uses [Extended Custom Post Types](https://github.com/johnbillion/extended-cpts) and [Extended Taxonomies](https://github.com/johnbillion/extended-taxos) for registering post types and taxonomies.

* Uses [CMB2](https://github.com/WebDevStudios/CMB2) for handling post meta boxes.

* Giving a field a key of `web`, `website`, or `url` will cause the field value to autolink on front end CPTD views

---

## Shortcodes

### [cptd-a-z-listing]

 * Displays an A-Z listing of all posts for the custom post type

 * **Attributes:** 

    * `post_types` The post types to be displayed, separated by comma `ex: 'book, movie'`

    * `list_style` The list style for the `li` HTML elements `ex: 'disc'`

---

### [cptd-terms]

* Displays a term list for one or more taxonomies

* **Attributes:**

    * `taxonomies`  The taxonomies to show terms for, separated by comma `ex: 'book_genre, movie_genre'`

    * `list_style` The list style for the `li` HTML elements `ex: 'none'`

---

## Filters

### ````cptd_register_pt````

#### Description

Use this filter to access the post type data before CPTD post types are registered.  The filtered object is an array
containing the arguments for [register\_extended\_post\_type](https://github.com/johnbillion/extended-cpts/wiki/Basic-usage), which is a wrapper for [register\_post\_type](https://codex.wordpress.org/Function_Reference/register_post_type).


#### Parameters
  
    $args = array(
    	'post_type' => (string),
    	'args' => (array, optional), 	// The arguments for `register_post_type`
    	'names' => (array, optional) 	// The corresponding parameter for `register_extended_post_type`
    )

  
#### Example

    add_filter( 'cptd_register_pt', 'my_register_pt' );
    function my_register_pt( $args ) {

    	if( $cpt['post_type'] == 'my_post_type' )
    		$cpt['args']['labels']['menu_name'] = 'Custom Menu Name';
    	
        return $cpt;
    }

---

### ````cptd_the_content````

#### Description

Use this filter to modify the post content for CPTD views.  Does not fire for non-CPTD page views.

#### Parameters

    (string) $content: The post content along with any appended fields for the current view

#### Return
    
    (string) You must return the altered HTML to be displayed

#### Example

Below, we're appending an additional field called `phone` below the default fields and post content.

    add_filter( 'cptd_the_content', 'my_the_content' );
    function my_the_content( $content ) {

        global $post;

        $phone = get_post_meta( $post->ID, 'phone', true );
        if( $phone ) $new_html = '<p>Phone: '. $phone .'</p>':

        return $content . $new_html;
    }

---

### ````cptd_field_value_{$field_name}````

Use this to filter a field value before it is displayed on the front end. Use your own field name (meta key) in place of `{$field_name}`.  Note that the filter fires whether or not the field has a value.

#### Parameters

    (string) $value: The field value to be displayed

    (CPTD_Field) $field: The field object that is being displayed


#### Return

    (string) You must return the altered field value

#### Example

Below, we are filtering the value of a field called `email` and adding a mailto link.

    add_filter( 'cptd_field_value_email', 'my_email_value_filter' );
    function my_email_value_filter( $value ){

        $value = "<a href='mailto:". $value . "' >" . $value . "</a>";
        return $value;

    }

---

### ````cptd_field_label_{$field_name}````

Use this filter to edit a filed's label and label wrap before it is displayed on the front end. Use your own field name (meta key) in place of `{$field_name}`. Note that the filter fires whether or not the field has a value.

#### Parameters

    (array) $label {
        'text'   => (string)   // The label text
        'before' => (string)   // HTML that comes before the label (Default: "<label>")
        'after'  => (string)   // HTML that comes after the label (Default: ": &nbsp; </label>")
    }

    (CPTD_Field) $field: The field object that is being displayed


#### Return

    (array)  You must return the altered $label array

#### Example

Below, we are setting the `first_name` field's label text to "First" and the `last_name` field's label text to "Last"

    add_filter( 'cptd_field_label_first_name', 'my_first_name_label' );
    function my_first_name_label( $label ) {
        $label['text'] = 'First';
        return $label;
    }

    add_filter( 'cptd_field_label_last_name', 'my_last_name_label' );
    function my_last_name_label( $label ) {
        $label['text'] = 'Last';
        return $label;
    }

---

### ````cptd_field_wrap_{$field_name}````

#### Parameters

    (array) $wrap {
        'before_tag'    => 'div',
        'classes'       => (array),
        'id'            => (string),
        'after_tag'     => 'div',
    }

    (CPTD_Field) $field: The field being displayed

#### Return

    (array) You must return the $wrap array

#### Example

Below is a fairly involved example that uses the `cptd_field_wrap` filter along with `cptd_field_value` and `cptd_field_label` to do the following:

* Append a space to the `first_name` field
* Remove individual labels for the `first_name` and `last_name` fields
* Remove the individual wrappers for the `first_name` and `last_name` fields and wrap them together in a single div

##### append a space to the first name

    add_filter( 'cptd_field_value_first_name', 'my_first_name_value' );
    function my_first_name_value( $value ) {
        return $value . ' ';
    }

##### empty out the labels for first and last name fields

    add_filter( 'cptd_field_label_first_name', 'my_name_label' );
    add_filter( 'cptd_field_label_last_name', 'my_name_label' );
    function my_name_label( $label ) {
        return array('before' => '', 'after' => '', 'text' => '');
    }

##### add a <p> wrapper around the first and last name fields 

    add_filter( 'cptd_field_wrap_first_name', 'my_name_wrap', 10, 2 );
    add_filter( 'cptd_field_wrap_last_name', 'my_name_wrap', 10, 2 );
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

### ````cptd_link_text````

Use to change the default "View Website" link text for auto detected URL fields (`web`, `website`, `url`)

#### Parameters

    (string)        $text      The link text currently set for display (default: "View Website")
    (CPTD_Field)    $field    The field object currently being displayed

#### Return

    (string) You must return the new link text for display

#### Examples

Below, we're changing the link text to 'Visit Webpage'

    add_filter( 'cptd_link_text', 'my_link_text' );
    function my_link_text() {
        return 'Visit Webpage';
    }


For a more complex example, we can get the post currently being displayed and incorporate a custom field with the link text. Note that we are checking first that the post has a specific post type, `author`.  We also check that the user has a value for the custom field `first_name`.

    add_filter( 'cptd_link_text', 'my_variable_link_text', 10, 2 );
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

### ````cptd_pt_description_wrap````

---

## Actions

### ````cptd_pre_get_posts````

Use this action to alter the global `$wp_query` for CPTD views.  It's essentially the same as `pre_get_posts`, except CPTD defaults are in place.  Also, there is no need to check whether we're viewing a page for a CPTD object or whether the query is the main query object, as this has been verified before the filter fires.  

Below are the default query arguments for a CPTD view:

* `order_by`: post_title
* `order`: ASC

#### Parameters

    $query: The same value passed by WP's `pre_get_posts` action, with the CPTD defaults in place

#### Example

Below, we are using the `cptd_pre_get_posts` filter to order CPTD posts by a field called `last_name`

    add_action( 'cptd_pre_get_posts', 'my_pre_get_posts' );
    function my_pre_get_posts( $query ) {

        $query->query_vars['orderby'] = 'meta_value';
        $query->query_vars['meta_key'] = 'last_name';
    }

---

### ````cptd_wp````

### ````cptd_enqueue_scripts````

### ````cptd_before_pt_description````

### ````cptd_after_pt_description````

---

### ````cptd_pre_render_field_{$field_name}````
### ````cptd_post_render_field_{$field_name}````

These actions allow users to insert their own HTML before (*pre*) or after (*post*) a field is rendered.  Use your own field name (meta key) in place of `{$field_name}`.  Note that the action fires whether or not the field has a value.

### Parameters

````$field```` (CPTD_Field) The field object being displayed

### Example

Below is an example to wrap a field called `email` in a div. Note the example doesn't use the `$field` object, although it is available inside the function.

    add_action('cptd_pre_render_field_email', 'my_pre_email');
    add_action('cptd_post_render_field_email', 'my_post_email');

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

## Functions

Here are some helper functions that you can call within your child theme

### ````is_cptd_view()````

Returns `true` if we are viewing a CPTD object and `false` otherwise.