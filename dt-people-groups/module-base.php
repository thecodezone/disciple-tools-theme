<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

/**
 * Class DT_People_Groups_Base
 * Load the core post type hooks into the Disciple Tools system
 */
class DT_People_Groups_Base extends DT_Module_Base {

    public $post_type = "peoplegroups";
    public $module = "peoplegroups_base";
    public $single_name = 'People Group';
    public $plural_name = 'People Groups';
    public static function post_type(){
        return 'peoplegroups';
    }

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        parent::__construct();
        if ( !self::check_enabled_and_prerequisites() ){
            return;
        }

        add_action( 'after_setup_theme', [ $this, 'after_setup_theme' ], 100 );
        add_filter( 'dt_set_roles_and_permissions', [ $this, 'dt_set_roles_and_permissions' ], 20, 1 );
        add_filter( "dt_filter_access_permissions", [ $this, "dt_filter_access_permissions" ], 20, 2 );

        add_filter( 'desktop_navbar_menu_options', [ $this, 'add_navigation_links' ], 25, 1 );
        add_filter( 'dt_nav_add_post_menu', [ $this, 'dt_nav_add_post_menu' ], 25, 1 );

    }

    public function after_setup_theme(){
        if ( class_exists( 'Disciple_Tools_Post_Type_Template' )) {
            new Disciple_Tools_Post_Type_Template( $this->post_type, $this->single_name, $this->plural_name );
        }
    }

    public function dt_set_roles_and_permissions( $expected_roles ){
        if ( isset( $expected_roles["administrator"] ) ){
            $expected_roles["administrator"]["permissions"]['edit_peoplegroups'] = true;
        }
        if ( isset( $expected_roles["dt_admin"] ) ){
            $expected_roles["dt_admin"]["permissions"]['edit_peoplegroups'] = true;
        }
        return $expected_roles;
    }

    public static function dt_filter_access_permissions( $permissions, $post_type ){
        if ( $post_type === self::post_type() ){
            $permissions = [];
        }
        return $permissions;
    }

    public function add_navigation_links( $tabs ) {
        if ( isset( $tabs[$this->post_type] ) ) {
            unset( $tabs[$this->post_type] );
        }
        return $tabs;
    }

    public function dt_nav_add_post_menu( $links ){
        if ( isset( $links[$this->post_type] ) ) {
            unset( $links[$this->post_type] );
        }
        return $links;
    }

}


