# Custom Post Type Directory
---

A directory plugin for WordPress, driven by the WP Custom Post Type environment.

## Features

* Creates custom post type and taxonomies from backend without coding
* Provides search widget that filters custom post type items by taxonomy terms and custom field values
* Provides default views but also allows you to create your own views inside theme's functions.php file for single listings, taxonomy archives, and search results
* Import functionality from .csv file to custom post type entries

## Shortcodes
* [cptd-terms]
  * Displays a list of taxonomy terms 
  * **Attributes:** The default for each attribute will be taken from the plugin settings, but can be overridden within the shortcode 
    * `taxonomy` The taxonomy whose terms we'll list
    * `show_count` ('true' or 'false') Whether or not to show the number of posts for each term
    * `show_empty` ('true' or 'false') Whether or not to show terms that don't have any posts
    * `show_title` ('true' or 'false') Whether or not to show the title (taxonomy name) at the beginning of the terms list

* [cptd-az-listing]
 * Displays an A-Z listing of all posts for the custom post type

## Notes

* Default behaviors for fields are not well-defined without Advanced Custom Fields plugin.  Using ACF adds support for field ordering and field types, as well as improving backend data entry experience

## Integration with theme

### Call these existing functions within your theme:

#### ```CPTD::default_fields($content, $type, $callback)```
* Display all fields for a post, with ACF ordering (within loop)
  * *$content :* the post content
  * *$type :* "single" or "multi"
  * *$callback :* a function which can be used in the theme to filter out unwanted fields

Example:

	(inside of loop){
    	cptdir_default_field_view($content, "single", "my_single_field_callback");
    }
    
Now we have to define the callback function *my_single_field_callback*

    # In this example, we're keeping the 'name' and 'email' fields from being displayed
    function my_single_field_callback($field){
        $reject = array("name", "email");
        if(in_array($field['name'], $reject)) return false;
        return true;
    }


#### ```cptdir_field($field)```
* Display a single field label and value within loop
 * *$field* : A string like 'field_name' or ACF array

---

### Define these functions within your theme to customize listing display

####```cptdir_custom_terms_list($terms)```
* Replace default terms list provided by directory front page and shortcode. You must return an HTML string
 * *$terms* : You'll have access to the terms belonging to the taxonomy that you select in the plugin options

####```cptdir_custom_single($content)```
* Hook into post content for single listing view, passing and returning post content if needed
 * *$content* : The post content

####```cptdir_custom_archive($content)```
* Hook into post content for archive list view, passing and returning post content if needed
 * *$content* : The post content

####```cptdir_custom_taxonomy_content($content)```
* Hook into post content for custom taxonomy archive listing, passing and return the post content if needed
 * *$content* : The post content