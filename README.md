
# Custom Post Type Directory

Directory management system based on Custom Post Types, Taxonomies, and Fields

## Features

* Creates custom post type and taxonomies from backend without coding

---

## Notes

* Default behaviors for fields are not well-defined without Advanced Custom Fields plugin.  Using ACF adds support for field ordering and field types, as well as improving backend data entry experience

---

## Shortcodes

---

## Filters

````cptd_register_pt````

### Description

Use this filter to access the post type data before CPTD post types are registered.  The filtered object is an array
containing the arguments for [register\_extended\_post\_type](https://github.com/johnbillion/extended-cpts/wiki/Basic-usage), which is a wrapper for [register\_post\_type](https://codex.wordpress.org/Function_Reference/register_post_type).

Define your function so that it accepts and returns the `$cpt` array below.

### Parameters
  
    $cpt = array(
    	'post_type' => (string),
    	'args' => (array, optional), 	// The arguments for `register_post_type`
    	'names' => (array, optional) 	// The corresponding parameter for `register_extended_post_type`
    )

  
### Example

    add_filter('cptd_register_pt', 'my_register_pt');
    function my_register_pt($args){
    	if($cpt['post_type'] == 'my_post_type')
    		$cpt['args']['labels']['menu_name'] = 'Custom Menu Name'
    	return $cpt;
    }

---

## Actions

````cptd_pre_get_posts````

Use this action to alter the query for CPTD views.  Does not fire on non-CPTD page views

### Parameters

    $query: The same $query passed via 'pre_get_posts'