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
        add_action( 'p2p_init', [ $this, 'p2p_init' ] );
        add_filter( 'dt_set_roles_and_permissions', [ $this, 'dt_set_roles_and_permissions' ], 20, 1 );
        add_filter( 'dt_custom_fields_settings', [ $this, 'dt_custom_fields_settings' ], 10, 2 );

        if ( ! dt_is_module_enabled( 'peoplegroups_ui' ) ) {
            add_filter( 'desktop_navbar_menu_options', [ $this, 'add_navigation_links' ], 25, 1 );
            add_filter( 'dt_nav_add_post_menu', [ $this, 'dt_nav_add_post_menu' ], 25, 1 );
        }
    }

    public function after_setup_theme(){
        if ( class_exists( 'Disciple_Tools_Post_Type_Template' )) {
            new Disciple_Tools_Post_Type_Template( $this->post_type, $this->single_name, $this->plural_name );
        }
    }
    public function dt_custom_fields_settings( $fields, $post_type ){
        if ( $post_type === $this->post_type ){

            $fields['rop3'] = [
                'name'        => __( 'ROP3', 'disciple_tools' ),
                'description' => '',
                'type'        => 'text',
                'default'     => '',
                'tile' => 'profile',
            ];
            $fields['rog3'] = [
                'name'        => __( 'ROP3', 'disciple_tools' ),
                'description' => '',
                'type'        => 'text',
                'default'     => '',
                'tile' => 'profile',
            ];
            $fields['peopleid3'] = [
                'name'        => __( 'ROP3', 'disciple_tools' ),
                'description' => '',
                'type'        => 'text',
                'default'     => '',
                'tile' => 'profile',
            ];

            $fields["contacts"] = [
                "name" => __( 'Contacts', 'disciple_tools' ),
                'description' => _x( 'The people groups represented by this contact.', 'Optional Documentation', 'disciple_tools' ),
                "type" => "connection",
                "post_type" => "contacts",
                "p2p_direction" => "to",
                "p2p_key" => "contacts_to_peoplegroups",
                'tile'     => 'other',
                'icon' => get_template_directory_uri() . "/dt-assets/images/people-group.svg",
            ];
            $fields["groups"] = [
                "name" => __( 'Groups', 'disciple_tools' ),
                'description' => _x( 'The people groups represented by this group.', 'Optional Documentation', 'disciple_tools' ),
                "type" => "connection",
                "post_type" => "groups",
                "p2p_direction" => "to",
                "p2p_key" => "groups_to_peoplegroups",
                'tile'     => 'other',
                'icon' => get_template_directory_uri() . "/dt-assets/images/people-group.svg",
            ];
        }

        return $fields;
    }

    public function p2p_init(){}

    public function dt_set_roles_and_permissions( $expected_roles ){

        $expected_roles["peoplegroups_admin"] = [
            "label" => __( 'People Groups Admin', 'disciple-tools-training' ),
            "description" => __( 'Admin access to all people', 'disciple-tools-training' ),
            "permissions" => []
        ];
        if ( !isset( $expected_roles["multiplier"] ) ){
            $expected_roles["multiplier"] = [
                "label" => __( 'Multiplier', 'disciple_tools' ),
                "permissions" => []
            ];
        }

        // if the user can access contact they also can access this post type
        foreach ( $expected_roles as $role => $role_value ){
            if ( isset( $expected_roles[$role]["permissions"]['access_contacts'] ) && $expected_roles[$role]["permissions"]['access_contacts'] ){
                $expected_roles[$role]["permissions"]['access_' . $this->post_type ] = true;
                $expected_roles[$role]["permissions"]['create_' . $this->post_type] = true;
            }
        }

        if ( isset( $expected_roles["administrator"] ) ){
            $expected_roles["administrator"]["permissions"]['view_any_'.$this->post_type ] = true;
            $expected_roles["administrator"]["permissions"][ 'dt_all_admin_' . $this->post_type] = true;
            $expected_roles["administrator"]["permissions"]['update_any_'.$this->post_type ] = true;
            $expected_roles["administrator"]["permissions"]['list_'.$this->post_type ] = true;
        }
        if ( isset( $expected_roles["dt_admin"] ) ){
            $expected_roles["dt_admin"]["permissions"]['view_any_'.$this->post_type ] = true;
            $expected_roles["dt_admin"]["permissions"]['update_any_'.$this->post_type ] = true;
            $expected_roles["dt_admin"]["permissions"][ 'dt_all_admin_' . $this->post_type] = true;
            $expected_roles["dt_admin"]["permissions"]['list_'.$this->post_type ] = true;
        }
        if ( isset( $expected_roles["peoplegroups_admin"] ) ){
            $expected_roles["peoplegroups_admin"]["permissions"]['view_any_'.$this->post_type ] = true;
            $expected_roles["peoplegroups_admin"]["permissions"]['update_any_'.$this->post_type ] = true;
            $expected_roles["peoplegroups_admin"]["permissions"][ 'dt_all_admin_' . $this->post_type] = true;
            $expected_roles["peoplegroups_admin"]["permissions"]['list_'.$this->post_type ] = true;
        }

        return $expected_roles;
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


