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

        add_action( 'p2p_init', [ $this, 'p2p_init' ] );
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
            /**
             * Basic framework fields used by post-type base
             * recommended to leave these alone
             */
            $fields['tags'] = [
                'name'        => __( 'Tags', 'disciple_tools' ),
                'description' => _x( 'A useful way to group related items.', 'Optional Documentation', 'disciple_tools' ),
                'type'        => 'multi_select',
                'default'     => [],
                'tile'        => 'other',
                'custom_display' => true,
            ];
            $fields["follow"] = [
                'name'        => __( 'Follow', 'disciple_tools' ),
                'type'        => 'multi_select',
                'default'     => [],
                'section'     => 'misc',
                'hidden'      => true
            ];
            $fields["unfollow"] = [
                'name'        => __( 'Un-Follow', 'disciple_tools' ),
                'type'        => 'multi_select',
                'default'     => [],
                'hidden'      => true
            ];
            $fields['tasks'] = [
                'name' => __( 'Tasks', 'disciple_tools' ),
                'type' => 'post_user_meta',
            ];
            $fields["duplicate_data"] = [
                "name" => 'Duplicates', //system string does not need translation
                'type' => 'array',
                'default' => [],
            ];
            $fields['assigned_to'] = [
                'name'        => __( 'Assigned To', 'disciple_tools' ),
                'description' => __( "Select the main person who is responsible for reporting on this record.", 'disciple_tools' ),
                'type'        => 'user_select',
                'default'     => '',
                'tile' => 'status',
                'icon' => get_template_directory_uri() . '/dt-assets/images/assigned-to.svg',
                "show_in_table" => 16,
                'custom_display' => true,
            ];
            $fields["requires_update"] = [
                'name'        => __( 'Requires Update', 'disciple_tools' ),
                'description' => '',
                'type'        => 'boolean',
                'default'     => false,
            ];
            // end basic framework fields


            $fields['status'] = [
                'name'        => __( 'Status', 'disciple_tools' ),
                'description' => _x( 'Set the current status.', 'field description', 'disciple_tools' ),
                'type'        => 'key_select',
                'default'     => [
                    'none' => [
                        'label' => __( 'No Engagement', 'disciple_tools' ),
                        'description' => _x( 'Unknown status.', 'field description', 'disciple_tools' ),
                        'color' => "#F43636"
                    ],
                    'engaging'   => [
                        'label' => __( 'Engaging', 'disciple_tools' ),
                        'description' => _x( 'Unengaged Unreached', 'field description', 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                    'reaching'   => [
                        'label' => __( 'Reaching', 'disciple_tools' ),
                        'description' => _x( 'Unengaged Unreached', 'field description', 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                ],
                'tile'     => 'details',
                'icon' => get_template_directory_uri() . '/dt-assets/images/status.svg',
                "default_color" => "#366184",
                "show_in_table" => 10,
            ];



            /**
             * Common and recommended fields
             */
            $fields['start_date'] = [
                'name'        => __( 'Start Date', 'disciple_tools' ),
                'description' => '',
                'type'        => 'date',
                'default'     => time(),
                'tile' => 'details',
                'icon' => get_template_directory_uri() . '/dt-assets/images/date-start.svg',
            ];


            $fields['location_grid'] = [
                'name'        => __( 'Locations', 'disciple_tools' ),
                'description' => _x( 'The general location where this contact is located.', 'Optional Documentation', 'disciple_tools' ),
                'type'        => 'location',
                'mapbox'    => false,
                "in_create_form" => true,
                "tile" => "details",
                "icon" => get_template_directory_uri() . "/dt-assets/images/location.svg",
            ];
            $fields['location_grid_meta'] = [
                'name'        => __( 'Locations', 'disciple_tools' ), //system string does not need translation
                'description' => _x( 'The general location where this contact is located.', 'Optional Documentation', 'disciple_tools' ),
                'type'        => 'location_meta',
                "tile"      => "details",
                'mapbox'    => false,
                'hidden' => true
            ];
            $fields["contact_address"] = [
                "name" => __( 'Address', 'disciple_tools' ),
                "icon" => get_template_directory_uri() . "/dt-assets/images/house.svg",
                "type" => "communication_channel",
                "tile" => "details",
                'mapbox'    => false,
                "customizable" => false
            ];
            if ( DT_Mapbox_API::get_key() ){
                $fields["contact_address"]["hidden"] = true;
                $fields["contact_address"]["mapbox"] = true;
                $fields["location_grid"]["mapbox"] = true;
                $fields["location_grid_meta"]["mapbox"] = true;
            }
            // end locations


            $fields["parents"] = [
                "name" => __( 'Parents', 'disciple_tools' ),
                'description' => '',
                "type" => "connection",
                "post_type" => $this->post_type,
                "p2p_direction" => "from",
                "p2p_key" => $this->post_type."_to_".$this->post_type,
                'tile' => 'connections',
                'icon' => get_template_directory_uri() . '/dt-assets/images/group-parent.svg',
                'create-icon' => get_template_directory_uri() . '/dt-assets/images/add-group.svg',
            ];
            $fields["peers"] = [
                "name" => __( 'Peers', 'disciple_tools' ),
                'description' => '',
                "type" => "connection",
                "post_type" => $this->post_type,
                "p2p_direction" => "any",
                "p2p_key" => $this->post_type."_to_peers",
                'tile' => 'connections',
                'icon' => get_template_directory_uri() . '/dt-assets/images/group-peer.svg',
                'create-icon' => get_template_directory_uri() . '/dt-assets/images/add-group.svg',
            ];
            $fields["children"] = [
                "name" => __( 'Children', 'disciple_tools' ),
                'description' => '',
                "type" => "connection",
                "post_type" => $this->post_type,
                "p2p_direction" => "to",
                "p2p_key" => $this->post_type."_to_".$this->post_type,
                'tile' => 'connections',
                'icon' => get_template_directory_uri() . '/dt-assets/images/group-child.svg',
                'create-icon' => get_template_directory_uri() . '/dt-assets/images/add-group.svg',
            ];
            // end generations

        }

        return $fields;
    }

    public function p2p_init(){
        /**
         * Parent and child connection
         */
        p2p_register_connection_type(
            [
                'name'         => $this->post_type."_to_".$this->post_type,
                'from'         => $this->post_type,
                'to'           => $this->post_type,
                'title'        => [
                    'from' => $this->plural_name . ' by',
                    'to'   => $this->plural_name,
                ],
            ]
        );
        /**
         * Peer connections
         */
        p2p_register_connection_type( [
            'name'         => $this->post_type."_to_peers",
            'from'         => $this->post_type,
            'to'           => $this->post_type,
        ] );

    }

    public function dt_set_roles_and_permissions( $expected_roles ){

        // give everyone ability to list
//        foreach ( $expected_roles as $role => $role_value ){
//            if ( isset( $expected_roles[$role]["permissions"]['access_contacts'] ) && $expected_roles[$role]["permissions"]['access_contacts'] ){
//                $expected_roles[$role]["permissions"]['list_all_' . $this->post_type ] = true;
//            }
//        }

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


