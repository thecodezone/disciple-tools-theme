<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

/**
 * Class DT_People_Groups_Progress
 * Load the core post type hooks into the Disciple Tools system
 */
class DT_People_Groups_Progress extends DT_Module_Base {

    public $post_type = "peoplegroups";
    public $module = "peoplegroups_progress";
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

        //setup tiles and fields
        add_filter( 'dt_details_additional_tiles', [ $this, 'dt_details_additional_tiles' ], 10, 2 );
        add_action( 'dt_details_additional_section', [ $this, 'dt_details_additional_section' ], 20, 2 );
        add_filter( 'dt_custom_fields_settings', [ $this, 'dt_custom_fields_settings' ], 10, 2 );

        // hooks
//        add_action( 'wp_enqueue_scripts', [ $this, 'scripts' ], 99 );
//        add_action( "post_connection_removed", [ $this, "post_connection_removed" ], 10, 4 );
//        add_action( "post_connection_added", [ $this, "post_connection_added" ], 10, 4 );
//        add_filter( "dt_post_update_fields", [ $this, "dt_post_update_fields" ], 10, 3 );
//        add_filter( "dt_post_create_fields", [ $this, "dt_post_create_fields" ], 10, 2 );
//        add_action( "dt_post_created", [ $this, "dt_post_created" ], 10, 3 );
//        add_action( "dt_comment_created", [ $this, "dt_comment_created" ], 10, 4 );

        //list
//        add_filter( "dt_user_list_filters", [ $this, "dt_user_list_filters" ], 20, 2 );
        add_filter( "dt_filter_access_permissions", [ $this, "dt_filter_access_permissions" ], 20, 2 );

    }

    public function dt_custom_fields_settings( $fields, $post_type ){
        if ( $post_type === $this->post_type ){




        }

        return $fields;
    }



    public function dt_details_additional_tiles( $tiles, $post_type = "" ){
        if ( $post_type === $this->post_type ){
            $tiles["progress"] = [ "label" => __( "Progress", 'disciple_tools' ) ];
        }
        return $tiles;
    }

    public function dt_details_additional_section( $section, $post_type ){

        if ( $post_type === $this->post_type ) {

            $record_fields = DT_Posts::get_post_field_settings( $post_type );
            $record = DT_Posts::get_post( $post_type, get_the_ID() );

            if ( isset( $record_fields["tags"]["tile"] ) && $record_fields["tags"]["tile"] === $section ) {
            }


            if ( 'progress' === $section ) {
                ?>
                <div class="cell small-12">
                    <div class="section-subheader">
                        1. Moving purposefully (G1)
                    </div>
                    <div>
                        <input />
                    </div>
                </div>
                <div class="cell small-12">
                    <div class="section-subheader">
                        2. Focused (G2)
                    </div>
                    <div>
                        <input />
                    </div>
                </div>
                <?php
            }

        } // post type
    }

    public static function dt_filter_access_permissions( $permissions, $post_type ){
        if ( $post_type === self::post_type() ){
            if ( DT_Posts::can_view_all( $post_type ) ){
                $permissions = [];
            }
        }
        return $permissions;
    }

    /**
     * action when a post connection is added during create or update
     *
     * The next three functions are added, removed, and updated of the same field concept
     */
    public function post_connection_added( $post_type, $post_id, $field_key, $value ){
        if ( $post_type === $this->post_type ){
            if ( $field_key === "assigned_to" ){
                DT_Posts::add_shared( $this->post_type, $post_id, (int) $value, null, false, false, true );
            }
        }
    }

    //action when a post connection is removed during create or update
    public function post_connection_removed( $post_type, $post_id, $field_key, $value ){
//        if ( $post_type === $this->post_type ){
//
//        }
    }

    //filter at the start of post update
    public function dt_post_update_fields( $fields, $post_type, $post_id ){
//        if ( $post_type === $this->post_type ){
//
//        }
        return $fields;
    }

    public function add_details_with_rop3( $post_id, $rop3 ) {
        $key = dt_joshua_project_key();
        $raw_response = wp_remote_get('https://joshuaproject.net/api/v2/people_groups?api_key='.$key.'&ROP3='. $rop3);
        if ( ! is_wp_error( $raw_response ) && isset( $raw_response['body'] ) ) {
            $response = json_decode( $raw_response['body'], true );
            $response = dt_recursive_sanitize_array($response);

            dt_write_log($response);
        }
    }


    //filter when a comment is created
    public function dt_comment_created( $post_type, $post_id, $comment_id, $type ){
//        if ( $post_type === $this->post_type ){
//        }
    }

    // filter at the start of post creation
    public function dt_post_create_fields( $fields, $post_type ){
//        if ( $post_type === $this->post_type ) {
//            if ( !isset( $fields["status"] ) ) {
//                $fields["status"] = "none";
//            }
//            if ( !isset( $fields["assigned_to"] ) ) {
//                $fields["assigned_to"] = sprintf( "user-%d", get_current_user_id() );
//            }
//            if ( isset( $fields["assigned_to"] ) ) {
//                if ( filter_var( $fields["assigned_to"], FILTER_VALIDATE_EMAIL ) ){
//                    $user = get_user_by( "email", $fields["assigned_to"] );
//                    if ( $user ) {
//                        $fields["assigned_to"] = $user->ID;
//                    } else {
//                        return new WP_Error( __FUNCTION__, "Unrecognized user", $fields["assigned_to"] );
//                    }
//                }
//                //make sure the assigned to is in the right format (user-1)
//                if ( is_numeric( $fields["assigned_to"] ) ||
//                    strpos( $fields["assigned_to"], "user" ) === false ){
//                    $fields["assigned_to"] = "user-" . $fields["assigned_to"];
//                }
//            }
//        }
        return $fields;
    }

    //action when a post has been created
    public function dt_post_created( $post_type, $post_id, $initial_fields ){
        if ( $post_type === $this->post_type ){
            do_action( "dt_'.$this->post_type.'_created", $post_id, $initial_fields );

            $post_array = DT_Posts::get_post( $this->post_type, $post_id, true, false );
            if ( isset( $post_array["assigned_to"] )) {
                if ( $post_array["assigned_to"]["id"] ) {
                    DT_Posts::add_shared( $this->post_type, $post_id, $post_array["assigned_to"]["id"], null, false, false, false );
                }
            }
        }
    }

    //list page filters function

    /**
     * Documentation
     * @link https://github.com/DiscipleTools/Documentation/blob/master/Theme-Core/list-query.md
     */
//    private static function get_my_status(){
//        global $wpdb;
//        $post_type = self::post_type();
//        $current_user = get_current_user_id();
//
//        $results = $wpdb->get_results( $wpdb->prepare( "
//            SELECT status.meta_value as status, count(a.ID) as count
//            FROM $wpdb->posts a
//            INNER JOIN $wpdb->postmeta status ON( status.post_id = a.ID AND status.meta_key = 'status' )
//            INNER JOIN $wpdb->postmeta as assigned_to ON a.ID=assigned_to.post_id
//                          AND assigned_to.meta_key = 'assigned_to'
//                          AND assigned_to.meta_value = CONCAT( 'user-', %s )
//            WHERE a.post_type = %s
//            GROUP BY status.meta_value;
//        ",  $current_user, $post_type ), ARRAY_A);
//
//        return $results;
//    }

    //list page filters function
//    private static function get_all_status_types(){
//        global $wpdb;
//
//        if ( current_user_can( 'view_any_'.self::post_type() ) ){
//            $results = $wpdb->get_results($wpdb->prepare( "
//                SELECT status.meta_value as status, count(a.ID) as count
//                FROM $wpdb->posts a
//                INNER JOIN $wpdb->postmeta status ON( status.post_id = a.ID AND status.meta_key = 'status' )
//                WHERE a.post_type = %s
//                GROUP BY status.meta_value;
//            ", self::post_type() ), ARRAY_A );
//        }
//
//        return $results;
//    }

    //build list page filters
//    public static function dt_user_list_filters( $filters, $post_type ){
//
//        if ( $post_type === self::post_type() ) {
//
//            $fields = DT_Posts::get_post_field_settings( $post_type );
//
//            /**
//             * ALL
//             */
//            if (current_user_can( 'view_any_' . self::post_type() )) {
//
//                $counts = self::get_all_status_types();
//
//                $status_counts = [];
//                $total_all = 0;
//                foreach ($counts as $count) {
//                    $total_all += $count["count"];
//                    dt_increment( $status_counts[$count["status"]], $count["count"] );
//                }
//                $filters["tabs"][] = [
//                    "key" => "all",
//                    "label" => _x( "All", 'List Filters', 'disciple_tools' ),
//                    "count" => $total_all,
//                    "order" => 10
//                ];
//                // add assigned to me filters
//                $filters["filters"][] = [
//                    'ID' => 'all',
//                    'tab' => 'all',
//                    'name' => _x( "All", 'List Filters', 'disciple_tools' ),
//                    'query' => [
//                        'sort' => '-post_date'
//                    ],
//                    "count" => $total_all
//                ];
//
//                foreach ($fields["status"]["default"] as $status_key => $status_value) {
//                    if (isset( $status_counts[$status_key] )) {
//                        $filters["filters"][] = [
//                            "ID" => 'all_' . $status_key,
//                            "tab" => 'all',
//                            "name" => $status_value["label"],
//                            "query" => [
//                                'status' => [ $status_key ],
//                                'sort' => '-post_date'
//                            ],
//                            "count" => $status_counts[$status_key]
//                        ];
//                    }
//                }
//            }
//
//            /**
//             * MY
//             */
//            $counts = self::get_my_status();
//            $status_counts = [];
//            $total_my = 0;
//            foreach ($counts as $count) {
//                $total_my += $count["count"];
//                dt_increment( $status_counts[$count["status"]], $count["count"] );
//            }
//
//            $filters["tabs"][] = [
//                "key" => "assigned_to_me",
//                "label" => _x( "Assigned to me", 'List Filters', 'disciple_tools' ),
//                "count" => $total_my,
//                "order" => 20
//            ];
//            // add assigned to me filters
//            $filters["filters"][] = [
//                'ID' => 'my_all',
//                'tab' => 'assigned_to_me',
//                'name' => _x( "All", 'List Filters', 'disciple_tools' ),
//                'query' => [
//                    'assigned_to' => [ 'me' ],
//                    'sort' => 'status'
//                ],
//                "count" => $total_my,
//            ];
//            foreach ($fields["status"]["default"] as $status_key => $status_value) {
//                if (isset( $status_counts[$status_key] )) {
//                    $filters["filters"][] = [
//                        "ID" => 'my_' . $status_key,
//                        "tab" => 'assigned_to_me',
//                        "name" => $status_value["label"],
//                        "query" => [
//                            'assigned_to' => [ 'me' ],
//                            'status' => [ $status_key ],
//                            'sort' => '-post_date'
//                        ],
//                        "count" => $status_counts[$status_key]
//                    ];
//                }
//            }
//        } // post type filter
//
//        return $filters;
//    }

    // scripts
//    public function scripts(){
//        if ( is_singular( $this->post_type ) ){
//            wp_enqueue_script( 'dt_people_groups', get_template_directory_uri() . '/dt-people-groups/module-ui.js', [
//                'jquery',
//                'details',
//                'lodash'
//            ], filemtime( get_template_directory() . '/dt-people-groups/module-ui.js' ), true );
//            wp_localize_script(
//                "dt_people_groups", "dtPeopleGroups", array(
//                    'root' => esc_url_raw( rest_url() ),
//                    'nonce' => wp_create_nonce( 'wp_rest' ),
//                    'current_user_login' => wp_get_current_user()->user_login,
//                    'current_user_id' => get_current_user_id(),
//                    'theme_uri' => get_template_directory_uri(),
//                    'images_uri' => disciple_tools()->admin_img_url,
//                )
//            );
//        }
//    }
}


