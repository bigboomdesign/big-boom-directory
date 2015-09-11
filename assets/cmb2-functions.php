<?php
/**
 * Include and setup custom metaboxes and fields.
 */
/**
 * Get the bootstrap!
 */
if( ! class_exists( 'CMB2_Bootstrap_210' ) ) {
	CPTD::req_file( cptdir_dir('/assets/cmb2/init.php') );

	# fix for the URL that cmb2 defines
	add_filter( 'cmb2_meta_box_url', 'update_cmb_meta_box_url' );
	function update_cmb_meta_box_url( $url ) {
	    // modify the url here
	    return cptdir_url('/assets/cmb2');
	}
}

add_action( 'cmb2_init', 'cptdir_metaboxes' );
function cptdir_metaboxes() {

	if( ! ( $pt = cptdir_get_pt() ) )  return;

    // Start with an underscore to hide fields from custom fields list
    $prefix = '_cptdir_';



    /**
     * Basic Info
     */

    # set up the meta box
    $basic_info = new_cmb2_box( array(
        'id'            => 'cptdir-basic-info',
        'title'         => __( 'Basic Info', 'cmb2' ),
        'object_types'  => array( $pt->name ),
        'context'       => 'normal',
        'priority'      => 'high',
        'show_names'    => true, // Show field names on the left
        // 'cmb_styles' => false, // false to disable the CMB stylesheet
        // 'closed'     => true, // Keep the metabox closed by default
    ) );

    # add fields
    $basic_info->add_field( array(
        'name'       => __( 'Email', 'cmb2' ),
        'id'         => $prefix . 'email',
        'type'       => 'text_email',
    ) );

    $basic_info->add_field( array(
        'name'       => __( 'Phone', 'cmb2' ),
        'id'         => $prefix . 'phone',
        'type'       => 'text',
    ) );
    $basic_info->add_field( array(
        'name'       => __( 'Fax', 'cmb2' ),
        'id'         => $prefix . 'fax',
        'type'       => 'text',
    ) );
    $basic_info->add_field( array(
        'name'       => __( 'Website', 'cmb2' ),
        'id'         => $prefix . 'website',
        'type'       => 'text_url',
    ) );
    $basic_info->add_field( array(
        'name'       => __( 'Street', 'cmb2' ),
        'id'         => $prefix . 'street',
        'type'       => 'text',
    ) );

    /**
     * Social Media
     */

    # set up the meta box
    $social = new_cmb2_box( array(
        'id'            => 'cptdir-social-fields',
        'title'         => __( 'Social Media Accounts', 'cmb2' ),
        'object_types'  => array( $pt->name ),
        'context'       => 'normal',
        'priority'      => 'high',
        'show_names'    => true, // Show field names on the left
        // 'cmb_styles' => false, // false to disable the CMB stylesheet
        // 'closed'     => true, // Keep the metabox closed by default
    ) );

    # add fields
    $social->add_field( array(
        'name'       => __( 'Facebook', 'cmb2' ),
        'id'         => $prefix . 'facebook',
        'type'       => 'text_url',
    ) );
    $social->add_field( array(
        'name'       => __( 'Twitter', 'cmb2' ),
        'id'         => $prefix . 'twitter',
        'type'       => 'text_url',
    ) );
    $social->add_field( array(
        'name'       => __( 'LinkedIn', 'cmb2' ),
        'id'         => $prefix . 'linkedin',
        'type'       => 'text_url',
    ) );
    $social->add_field( array(
        'name'       => __( 'Pinterest', 'cmb2' ),
        'id'         => $prefix . 'pinterest',
        'type'       => 'text_url',
    ) );
    $social->add_field( array(
        'name'       => __( 'Instagram', 'cmb2' ),
        'id'         => $prefix . 'instagram',
        'type'       => 'text_url',
    ) );
    $social->add_field( array(
        'name'       => __( 'Google Plus', 'cmb2' ),
        'id'         => $prefix . 'google_plus',
        'type'       => 'text_url',
    ) );
}