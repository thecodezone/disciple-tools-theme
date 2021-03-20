<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

/**
 * Class DT_People_Groups_UI
 * Load the core post type hooks into the Disciple Tools system
 */
class DT_People_Groups_UI extends DT_Module_Base {

    public $post_type = "peoplegroups";
    public $module = "peoplegroups_ui";
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

        add_action( 'p2p_init', [ $this, 'p2p_init' ] );

        //setup tiles and fields
        add_filter( 'dt_details_additional_tiles', [ $this, 'dt_details_additional_tiles' ], 10, 2 );
        add_action( 'dt_details_additional_section', [ $this, 'dt_details_additional_section' ], 20, 2 );
        add_action( 'wp_enqueue_scripts', [ $this, 'scripts' ], 99 );
        add_filter( 'dt_custom_fields_settings', [ $this, 'dt_custom_fields_settings' ], 10, 2 );

        // hooks
        add_action( "post_connection_removed", [ $this, "post_connection_removed" ], 10, 4 );
        add_action( "post_connection_added", [ $this, "post_connection_added" ], 10, 4 );
        add_filter( "dt_post_update_fields", [ $this, "dt_post_update_fields" ], 10, 3 );
        add_filter( "dt_post_create_fields", [ $this, "dt_post_create_fields" ], 10, 2 );
        add_action( "dt_post_created", [ $this, "dt_post_created" ], 10, 3 );
        add_action( "dt_comment_created", [ $this, "dt_comment_created" ], 10, 4 );

        //list
        add_filter( "dt_user_list_filters", [ $this, "dt_user_list_filters" ], 20, 2 );
        add_filter( "dt_filter_access_permissions", [ $this, "dt_filter_access_permissions" ], 20, 2 );

    }

    public function dt_set_roles_and_permissions( $expected_roles ){

//        foreach ( $expected_roles as $role => $role_value ){
//            if ( isset( $expected_roles[$role]["permissions"]['access_contacts'] ) && $expected_roles[$role]["permissions"]['access_contacts'] ){
//                $expected_roles[$role]["permissions"]['list_all_' . $this->post_type ] = true;
//            }
//        }
//
//        if ( isset( $expected_roles["administrator"] ) ){
//            $expected_roles["administrator"]["permissions"]['view_any_'.$this->post_type ] = true;
//            $expected_roles["administrator"]["permissions"]['access_'.$this->post_type ] = true;
//        }
//        if ( isset( $expected_roles["dt_admin"] ) ){
//            $expected_roles["dt_admin"]["permissions"]['view_any_'.$this->post_type ] = true;
//            $expected_roles["dt_admin"]["permissions"]['access_'.$this->post_type ] = true;
//        }
//        return $expected_roles;
    }

    public function dt_custom_fields_settings( $fields, $post_type ){
        if ( $post_type === $this->post_type ){
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

            $fields['status'] = [
                'name'        => __( 'Status', 'disciple_tools' ),
                'description' => _x( 'Set the current status.', 'field description', 'disciple_tools' ),
                'type'        => 'key_select',
                'default'     => [
                    'inactive' => [
                        'label' => __( 'Inactive', 'disciple_tools' ),
                        'description' => _x( 'Inactive', 'field description', 'disciple_tools' ),
                        'color' => "#F43636"
                    ],
                    'active'   => [
                        'label' => __( 'Active', 'disciple_tools' ),
                        'description' => _x( 'Active', 'field description', 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                ],
                'tile'     => '',
                'icon' => get_template_directory_uri() . '/dt-assets/images/status.svg',
                "default_color" => "#366184",
                "show_in_table" => 10,
                "in_create_form" => true,
            ];
            $fields["subassigned"] = [
                "name" => __( "Sub-assigned to", 'disciple_tools' ),
                "description" => __( "Contact or User assisting the Assigned To user to follow up with the contact.", 'disciple_tools' ),
                "type" => "connection",
                "post_type" => "contacts",
                "p2p_direction" => "to",
                "p2p_key" => "peoplegroups_to_subassigned",
                "tile" => "",
                "custom_display" => false,
                'icon' => get_template_directory_uri() . "/dt-assets/images/subassigned.svg",
            ];

            /**
             * Common and recommended fields
             */
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
        p2p_register_connection_type(
            [
                'name'         => "peoplegroups_to_subassigned",
                'from'         => $this->post_type,
                'to'           => 'contacts',
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


    public function dt_details_additional_tiles( $tiles, $post_type = "" ){
        if ( $post_type === $this->post_type ){
            $tiles["profile"] = [ "label" => __( "Profile", 'disciple_tools' ) ];
            $tiles["other"] = [ "label" => __( "Other", 'disciple_tools' ) ];
        }
        return $tiles;
    }

    public function dt_details_additional_section( $section, $post_type ){
        if ( $post_type === $this->post_type && $section === "status" ){
            $record = DT_Posts::get_post( $post_type, get_the_ID() );
            $record_fields = DT_Posts::get_post_field_settings( $post_type );
            ?>

            <div class="cell small-12 medium-4">
                <?php render_field_for_display( "status", $record_fields, $record, true ); ?>
            </div>
            <div class="cell small-12 medium-4">
                <div class="section-subheader">
                    <img src="<?php echo esc_url( get_template_directory_uri() ) . '/dt-assets/images/assigned-to.svg' ?>">
                    <?php echo esc_html( $record_fields["assigned_to"]["name"] )?>
                    <button class="help-button" data-section="assigned-to-help-text">
                        <img class="help-icon" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/help.svg' ) ?>"/>
                    </button>
                </div>

                <div class="assigned_to details">
                    <var id="assigned_to-result-container" class="result-container assigned_to-result-container"></var>
                    <div id="assigned_to_t" name="form-assigned_to" class="scrollable-typeahead">
                        <div class="typeahead__container">
                            <div class="typeahead__field">
                                    <span class="typeahead__query">
                                        <input class="js-typeahead-assigned_to input-height"
                                               name="assigned_to[query]" placeholder="<?php echo esc_html_x( "Search Users", 'input field placeholder', 'disciple_tools' ) ?>"
                                               autocomplete="off">
                                    </span>
                                <span class="typeahead__button">
                                        <button type="button" class="search_assigned_to typeahead__image_button input-height" data-id="assigned_to_t">
                                            <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_down.svg' ) ?>"/>
                                        </button>
                                    </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="cell small-12 medium-4">
                <?php render_field_for_display( "subassigned", $record_fields, $record, true ); ?>
            </div>
        <?php }


        if ( $post_type === $this->post_type && $section === "profile" ){
            $record = DT_Posts::get_post( $post_type, get_the_ID() );
            $record_fields = DT_Posts::get_post_field_settings( $post_type );

            if ( isset( $record['rop3'] ) && ! empty( $record['rop3'] ) ) {
                ?>
                <script type="text/javascript">
                    //let PEOPLE_GROUP_ID = '<?php //echo $record['jp_people_id_3'] ?>//'
                    let DOMAIN = 'https://api.joshuaproject.net';
                    let ROP3 = '<?php echo $record['rop3'] ?>'
                    let API_KEY = 'vinskxSNWQKH';
                    jQuery(document).ready(function($) {
                        $.ajax({
                            // @link https://api.joshuaproject.net/v1/docs/available_api_requests#!/people_groups/getAllPeopleGroupWithFilters_get_0
                            url: DOMAIN+'/v1/people_groups.json',
                            dataType: 'json',
                            data: {api_key: API_KEY, rop3: ROP3},
                            type: 'GET'
                        })
                            .done(function(data) {
                                console.log(data)

                                // var unreached = data[0];
                                // /* Set the text of each class to the appropriate data */
                                // $('.country-name').text(unreached['Ctry']);
                                // $('.pg-language').text(unreached['PrimaryLanguageName']);
                                // $('.pg-name').text(unreached['PeopNameInCountry']);
                                // $('.pg-religion').text(unreached['PrimaryReligion']);
                                // $('.pg-scale').text(unreached['JPScale']);
                                // $('.pg-scale-text').text(unreached['JPScaleText']);
                                // /* Handle the two links that need URL's*/
                                // $('.country-link').attr('href', unreached['CountryURL']);
                                // $('.pg-link').attr('href', unreached['PeopleGroupURL']);
                                // /* Append the images */
                                // var pgSettings = {'height': '160px', 'width': '128px'};
                                // var pgImg = $('<img/>').attr('src', unreached['PeopleGroupPhotoURL']).css(pgSettings);
                                // $('#people-group-image').append(pgImg);
                                // var scaleImg = $('<img/>').attr('src', unreached['JPScaleImageURL']);
                                // $('#progress-scale-image').append(scaleImg);
                                // /* Set the Percent Evangelical */
                                // if (unreached['PercentEvangelical'] == null) {
                                //     percent_evangelical = '0.00';
                                // } else {
                                //     percent_evangelical = parseFloat(unreached['PercentEvangelical']).toFixed(2);
                                // };
                                // $('.pg-evangelical').text(percent_evangelical+'%');
                                // /* Set the Population */
                                // $('.pg-population').text(numberWithCommas(unreached['Population']));
                                // /* Fade in the widget */
                                // $('div#jp_widget').fadeIn('slow');
                            })
                            .fail(function(jqXHR, textStatus, errorThrown) {
                                var pTagSettings = {'color': 'red', 'font-weight': 'bold'};
                                var pTag = $('<p/>').text('There was an error: '+errorThrown).css(pTagSettings);
                                $('body').prepend(pTag);
                            });
                    });
                    /* Number formating method. */
                    function numberWithCommas(x) {
                        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                    }
                </script>
<!--                <img src="http://www.joshuaproject.net/profiles/photos/p15641.jpg">-->
                <?php
            }
            ?>
            <?php
        }


        if ( $post_type === $this->post_type && $section === "other" ) :
            $fields = DT_Posts::get_post_field_settings( $post_type );
            ?>
            <div class="section-subheader">
                <?php echo esc_html( $fields["tags"]["name"] ) ?>
            </div>
            <div class="tags">
                <var id="tags-result-container" class="result-container"></var>
                <div id="tags_t" name="form-tags" class="scrollable-typeahead typeahead-margin-when-active">
                    <div class="typeahead__container">
                        <div class="typeahead__field">
                            <span class="typeahead__query">
                                <input class="js-typeahead-tags input-height"
                                       name="tags[query]"
                                       placeholder="<?php echo esc_html( sprintf( _x( "Search %s", "Search 'something'", 'disciple_tools' ), $fields["tags"]['name'] ) )?>"
                                       autocomplete="off">
                            </span>
                            <span class="typeahead__button">
                                <button type="button" data-open="create-tag-modal" class="create-new-tag typeahead__image_button input-height">
                                    <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/tag-add.svg' ) ?>"/>
                                </button>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif;
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
        if ( $post_type === $this->post_type ){
            if ( isset( $fields['assigned_to'] ) ){
                DT_Posts::add_shared( $this->post_type, $post_id, (int) $fields['assigned_to'], null, false, false, true );
            }
            if ( isset( $fields['rop3'] ) && ! empty( $fields['rop3'] ) ){
                dt_write_log('rop3 changed');
                $this->add_details_with_rop3( $post_id, $fields['rop3'] );


            }
        }
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
        if ( $post_type === $this->post_type ) {
            if ( !isset( $fields["status"] ) ) {
                $fields["status"] = "none";
            }
            if ( !isset( $fields["assigned_to"] ) ) {
                $fields["assigned_to"] = sprintf( "user-%d", get_current_user_id() );
            }
            if ( isset( $fields["assigned_to"] ) ) {
                if ( filter_var( $fields["assigned_to"], FILTER_VALIDATE_EMAIL ) ){
                    $user = get_user_by( "email", $fields["assigned_to"] );
                    if ( $user ) {
                        $fields["assigned_to"] = $user->ID;
                    } else {
                        return new WP_Error( __FUNCTION__, "Unrecognized user", $fields["assigned_to"] );
                    }
                }
                //make sure the assigned to is in the right format (user-1)
                if ( is_numeric( $fields["assigned_to"] ) ||
                    strpos( $fields["assigned_to"], "user" ) === false ){
                    $fields["assigned_to"] = "user-" . $fields["assigned_to"];
                }
            }
        }
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
    private static function get_my_status(){
        global $wpdb;
        $post_type = self::post_type();
        $current_user = get_current_user_id();

        $results = $wpdb->get_results( $wpdb->prepare( "
            SELECT status.meta_value as status, count(a.ID) as count
            FROM $wpdb->posts a
            INNER JOIN $wpdb->postmeta status ON( status.post_id = a.ID AND status.meta_key = 'status' )
            INNER JOIN $wpdb->postmeta as assigned_to ON a.ID=assigned_to.post_id
                          AND assigned_to.meta_key = 'assigned_to'
                          AND assigned_to.meta_value = CONCAT( 'user-', %s )
            WHERE a.post_type = %s
            GROUP BY status.meta_value;
        ",  $current_user, $post_type ), ARRAY_A);

        return $results;
    }

    //list page filters function
    private static function get_all_status_types(){
        global $wpdb;

        if ( current_user_can( 'view_any_'.self::post_type() ) ){
            $results = $wpdb->get_results($wpdb->prepare( "
                SELECT status.meta_value as status, count(a.ID) as count
                FROM $wpdb->posts a
                INNER JOIN $wpdb->postmeta status ON( status.post_id = a.ID AND status.meta_key = 'status' )
                WHERE a.post_type = %s
                GROUP BY status.meta_value;
            ", self::post_type() ), ARRAY_A );
        }

        return $results;
    }

    //build list page filters
    public static function dt_user_list_filters( $filters, $post_type ){

        if ( $post_type === self::post_type() ) {

            $fields = DT_Posts::get_post_field_settings( $post_type );

            /**
             * ALL
             */
            if (current_user_can( 'view_any_' . self::post_type() )) {

                $counts = self::get_all_status_types();

                $status_counts = [];
                $total_all = 0;
                foreach ($counts as $count) {
                    $total_all += $count["count"];
                    dt_increment( $status_counts[$count["status"]], $count["count"] );
                }
                $filters["tabs"][] = [
                    "key" => "all",
                    "label" => _x( "All", 'List Filters', 'disciple_tools' ),
                    "count" => $total_all,
                    "order" => 10
                ];
                // add assigned to me filters
                $filters["filters"][] = [
                    'ID' => 'all',
                    'tab' => 'all',
                    'name' => _x( "All", 'List Filters', 'disciple_tools' ),
                    'query' => [
                        'sort' => '-post_date'
                    ],
                    "count" => $total_all
                ];

                foreach ($fields["status"]["default"] as $status_key => $status_value) {
                    if (isset( $status_counts[$status_key] )) {
                        $filters["filters"][] = [
                            "ID" => 'all_' . $status_key,
                            "tab" => 'all',
                            "name" => $status_value["label"],
                            "query" => [
                                'status' => [ $status_key ],
                                'sort' => '-post_date'
                            ],
                            "count" => $status_counts[$status_key]
                        ];
                    }
                }
            }

            /**
             * MY
             */
            $counts = self::get_my_status();
            $status_counts = [];
            $total_my = 0;
            foreach ($counts as $count) {
                $total_my += $count["count"];
                dt_increment( $status_counts[$count["status"]], $count["count"] );
            }

            $filters["tabs"][] = [
                "key" => "assigned_to_me",
                "label" => _x( "Assigned to me", 'List Filters', 'disciple_tools' ),
                "count" => $total_my,
                "order" => 20
            ];
            // add assigned to me filters
            $filters["filters"][] = [
                'ID' => 'my_all',
                'tab' => 'assigned_to_me',
                'name' => _x( "All", 'List Filters', 'disciple_tools' ),
                'query' => [
                    'assigned_to' => [ 'me' ],
                    'sort' => 'status'
                ],
                "count" => $total_my,
            ];
            foreach ($fields["status"]["default"] as $status_key => $status_value) {
                if (isset( $status_counts[$status_key] )) {
                    $filters["filters"][] = [
                        "ID" => 'my_' . $status_key,
                        "tab" => 'assigned_to_me',
                        "name" => $status_value["label"],
                        "query" => [
                            'assigned_to' => [ 'me' ],
                            'status' => [ $status_key ],
                            'sort' => '-post_date'
                        ],
                        "count" => $status_counts[$status_key]
                    ];
                }
            }
        } // post type filter

        return $filters;
    }

    // scripts
    public function scripts(){
        if ( is_singular( $this->post_type ) ){
            wp_enqueue_script( 'dt_people_groups', get_template_directory_uri() . '/dt-people-groups/module-ui.js', [
                'jquery',
                'details',
                'lodash'
            ], filemtime( get_template_directory() . '/dt-people-groups/module-ui.js' ), true );
            wp_localize_script(
                "dt_people_groups", "dtPeopleGroups", array(
                    'root' => esc_url_raw( rest_url() ),
                    'nonce' => wp_create_nonce( 'wp_rest' ),
                    'current_user_login' => wp_get_current_user()->user_login,
                    'current_user_id' => get_current_user_id(),
                    'theme_uri' => get_template_directory_uri(),
                    'images_uri' => disciple_tools()->admin_img_url,
                )
            );
        }
    }
}


