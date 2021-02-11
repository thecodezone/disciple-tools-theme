<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

/**
 * Test that DT_Module_Base has loaded
 */
if ( ! class_exists( 'DT_Module_Base' ) ) {
    dt_write_log( 'Disciple Tools System not loaded. Cannot load custom post type.' );
    return;
}

/**
 * Add any modules required or added for the post type
 */
add_filter( 'dt_post_type_modules', function( $modules ){

    $modules["peoplegroups_base"] = [
        "name" => "People Groups",
        "enabled" => true,
        "locked" => true,
        "prerequisites" => [ "contacts_base" ],
        "post_type" => "peoplegroups",
        "description" => "People Groups"
    ];
    $modules["peoplegroups_ui"] = [
        "name" => "People Groups",
        "enabled" => false,
        "locked" => false,
        "prerequisites" => [ "contacts_base", "peoplegroups_base" ],
        "post_type" => "peoplegroups",
        "description" => "People Groups Tab and UI"
    ];

    return $modules;
}, 20, 1 );

require_once 'module-base.php';
DT_People_Groups_Base::instance();

//require_once 'module-ui.php';
//DT_People_Groups_UI::instance();
