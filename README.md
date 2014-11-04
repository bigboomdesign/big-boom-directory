# Custom Post Type Directory
---

A directory plugin for WordPress, driven by the WP Custom Post Type environment.

##Features

* Creates custom post type and taxonomies from backend without coding
* Provides search widget that filters custom post type items by taxonomy terms and custom field values
* Provides default views but also allows you to create your own views inside theme's functions.php file for single listings, taxonomy archives, and search results
* Import functionality from .csv file to custom post type entries

## Notes

* Default behaviors are not well-defined without Advanced Custom Fields plugin.  Using ACF adds support for field ordering and field types, as well as imporving backend data entry experience

## Integration with theme

### Call these existing functions within your theme:
* Get a list of all fields for a post, with ACF ordering (within loop)
 * ```cptdir_default_field_view()```

* Display a single field (within loop, must pass a field name string or ACF array)
 * ```cptdir_field()```

### Define these functions within your theme to customize listing display
* Hook into post content for single listing view, passing and returning post content if needed
 * ```cptdir_custom_single($content)```
* Hook into post content for custom taxonomy archive listing, passing and return the post content if needed
 * ```cptdir_custom_taxonomy_content($content)```

