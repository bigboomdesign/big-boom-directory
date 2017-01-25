=== Big Boom Directory ===
Contributors: bigboomdesign, michaelhull, GregGay
Tags: directory, custom-post-type, post-type, taxonomy, custom-fields
Requires at least: 4.0
Tested up to: 4.7
Stable tag: 2.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Directory management system based on Custom Post Types, Taxonomies, and Fields

== Description ==

Big Boom Directory is a directory management plugin for WordPress that utilizes Custom Post Types, Taxonomies, and Fields.  The plugin allows you to create a powerful ecosystem within your WordPress site to showcase your listings and the data associated with them.

## Features

* Create and manage custom post types and taxonomies from WP Admin.  Post type settings include:

    * Post type labels
    * WP Admin menu icon and position
    * URL slug and post type name/handle
    * Basic query arguments like 'Posts per page', 'Orderby', and 'Order'
    * Basic post type settings like 'Public' and 'Has Archive', 'Supports', and 'Exclude From Search'
    * Support for REST API using 'show\_in\_rest' and 'rest_base' arguments
    * Use hooks to add your own post type settings

* Add content using the WYSIWYG to act as the post type description for archive pages, and provide an option to show taxonomy term descriptions on term archive pages (for themes that don't do this by default).

* Pick and choose fields from Advanced Custom Fields groups to be displayed on single and archive views.  Compatible with both ACF and ACF Pro.

* Choose image size and alignment for single and archive views with ACF image fields

* Automatically detect URL and social media fields, converting them into links

* Support for ACF and ACF Pro field types like checkboxes, image, date picker, gallery field (with integration using Lightbox), Google Map, oEmbed

* Full-featured advanced search widget with customizable filters and field selection for the search results display

* Use hooks to further customize core functionality like post type editing and registration, front end field display and value processing, and more

For more details, check out the plugin's [GitHub](https://github.com/bigboomdesign/big-boom-directory) page.

== Installation ==

* Go To Plugins >> Add New
* Either search for "Big Boom" or Upload the .zip file downloaded here.
* Once installed, go to the `Directory` admin menu item and create your first post type
* Once you have a post type, you can easily add posts to that post type or create taxonomies associated with your post type
* We recommend using the [Advanced Custom Fields](https://wordpress.org/plugins/advanced-custom-fields) plugin, which opens up a large set up functionality within Big Boom Directory regarding field placement for the single and archive views for your post types

== Frequently Asked Questions ==

None yet.

== Screenshots ==

None yet.

== Changelog ==

= 2.3.0 =

* Add support for `posts_per_page` query argument
* Add ability to change post type supported features from within the UI
* Add option to insert term descriptions on term archive pages
* Quick link to view post type archive from within WP Admin menu
* Add actions to insert content before and after the search widget container
* Add filter to modify the auto-generated post excerpt on non-core WP views (like search widget results)
* Bug fix: supress the before/after description hooks for post types and terms with no description
* Bug fix for settings page: hide custom field key when not applicable
* Bug fix: custom images sizes now show up in plugin settings and post type settings
* Bug fix: search widget results would previously display fields multiple times in certain cases

= 2.2.1 =

* Improve support for ACF Pro
* Nest hierarchical terms in search widget
* Bug fix for 404 on single view when missing `orderby` field value
* Bug fix for TinyMCE shortcode builder

= 2.2.0 =

* Added a shortcode builder for the TinyMCE editor
* Cache the database calls that get post type data for each page load
* Updates to search widget
    * Added option to show the widget on the search results page
    * Add action for adding additional field types in backend search widget form
    * Add actions to insert content before and after front end search filters
    * Add filter for the WP_Query arguments on the search results page
    * Added action that executes after each search widget form instance is loaded in WP Admin
    * Show all search results instead of the default of 10
* Added support for Google Map field type in ACF
* Add hookable actions before and after the fields wrap element
* Remove row actions when viewing post types in the trash
* Bug fix for plugin action links
* Updated dependencies
    * CMB2 (2.2.1)
    * Extended CPT's (3.0.1)

= 2.1.0 = 

* Flush rewrite rules whenever a slug is added/deleted/updated for a post type or taxonomy
* Add support for ACF checkbox fields
* Added option to show or hide post types in the WP Admin menu
* Added `bbd_the_excerpt` filter for archive views instead of using `bbd_the_content`
* Improve row links for post type and taxonomy backend listing screens
* Limit the "View Post Type" link in the admin bar to those that are public and have an archive
* Make 'right' the default image alignment image fields
* Bug fix for multisite plugin action links
* Bug fix for search widget in WP Theme Customizer
* Add Big Boom logo for Directory post type icon
* Moved taxonomy name/handle into the Advanced Taxonomy Settings meta box

= 2.0.0 =

Initial release

