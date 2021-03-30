<?php
/**
 * Contains create, update and delete functions for people groups, wrapping access to
 * the database
 *
 * @package  Disciple_Tools
 * @category Plugin
 * @author   Chasm.Solutions & Kingdom.Training
 * @since    0.1.0
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly.

/**
 * Class Disciple_Tools_People_Groups_Admin
 */
class Disciple_Tools_People_Groups_Admin
{
    /**
     * Get JP csv file contents and return as array.
     * @return array
     */
    public static function get_jp_source( $associative = true ) {
        if ( ! file_exists( __DIR__ . "/csv/jp-people-groups.csv" ) ) {
            $zip_file = __DIR__ . "/csv/jp-people-groups.csv.zip";
            $zip = new ZipArchive();
            $extract_path = __DIR__ . "/csv/";
            if ($zip->open( $zip_file ) != "true")
            {
                error_log( "Error :- Unable to open the Zip File" );
            }
            $zip->extractTo( $extract_path );
            $zip->close();
        }

        $jp_csv = [];
        $handle = fopen( __DIR__ . "/csv/jp-people-groups.csv", "r" );
        if ( $handle !== false ) {
            while (( $data = fgetcsv( $handle, 0, "," ) ) !== false) {
                $jp_csv_raw[] = $data;
            }
            fclose( $handle );
            if ( $associative ) {
                foreach ( $jp_csv_raw as $row ) {
                    $jp_csv[$row[16]] = $row;
                }
                unset( $jp_csv['pg_unique_key'] );
            } else {
                $jp_csv = $jp_csv_raw;
            }
        }
        return $jp_csv;
    }

    public static function get_jp_unreached() {
        if ( ! file_exists( __DIR__ . "/csv/jp-unreached.csv" ) ) {
            $zip_file = __DIR__ . "/csv/jp-unreached.csv.zip";
            $zip = new ZipArchive();
            $extract_path = __DIR__ . "/csv/";
            if ($zip->open( $zip_file ) != "true")
            {
                error_log( "Error :- Unable to open the Zip File" );
            }
            $zip->extractTo( $extract_path );
            $zip->close();
        }

        $jp_csv = [];
        $handle = fopen( __DIR__ . "/csv/jp-unreached.csv", "r" );
        if ( $handle !== false ) {
            while (( $data = fgetcsv( $handle, 0, "," ) ) !== false) {
                $jp_csv[] = $data;
            }
            fclose( $handle );
        }

        return $jp_csv;
    }

    public static function get_jp_full_json() {
        if ( ! file_exists( __DIR__ . "/json/jp-people-groups.json" ) ) {
            $zip_file = __DIR__ . "/json/jp-people-groups.json.zip";
            $zip = new ZipArchive();
            $extract_path = __DIR__ . "/json/";
            if ($zip->open( $zip_file ) != "true")
            {
                error_log( "Error :- Unable to open the Zip File" );
            }
            $zip->extractTo( $extract_path );
            $zip->close();
        }

        $json = file_get_contents( __DIR__ . "/json/jp-people-groups.json" );
        return json_decode( $json );
    }

    public static function get_jp_unreached_json() {
        if ( ! file_exists( __DIR__ . "/json/jp-unreached.json" ) ) {
            $zip_file = __DIR__ . "/json/jp-unreached.json.zip";
            $zip = new ZipArchive();
            $extract_path = __DIR__ . "/json/";
            if ($zip->open( $zip_file ) != "true")
            {
                error_log( "Error :- Unable to open the Zip File" );
            }
            $zip->extractTo( $extract_path );
            $zip->close();
        }

        $json = file_get_contents( __DIR__ . "/json/jp-unreached.json" );
        return json_decode( $json, true );
    }

    public static function search_csv( $search ) { // gets a list by country
        if ( ! current_user_can( 'manage_dt' ) ) {
            return new WP_Error( __METHOD__, 'Insufficient permissions', [] );
        }
        $data = self::get_jp_source();
        $result = [];
        foreach ( $data as $row ) {
            if ( $row[2] === $search ) {
                $result[] = $row;
            }
        }
        return $result;
    }

    public static function search_csv_by_rop3( $search ) { // gets a list by country
        if ( ! current_user_can( 'manage_dt' ) ) {
            return new WP_Error( __METHOD__, 'Insufficient permissions', [] );
        }
        $data = self::get_jp_source();
        $result = [];
        foreach ( $data as $row ) {
            if ( $row[3] === $search ) {
                $result[] = $row;
            }
        }
        return $result;
    }

    public static function get_country_dropdown() {
        if ( ! current_user_can( 'manage_dt' ) ) {
            return new WP_Error( __METHOD__, 'Insufficient permissions', [] );
        }
        $data = self::get_jp_source();
        $all_names = array_column( $data, 2 );
        $unique_names = array_unique( $all_names );
        unset( $unique_names[0] );

        asort( $unique_names );
        return $unique_names;
    }

    /**
     * Add Single People Group
     *
     * @param $rop3
     * @param $country
     *
     * @return array|WP_Error
     */
    public static function add_single_people_group( $rop3, $country ) {
        if ( ! current_user_can( 'manage_dt' ) ) {
            return new WP_Error( __METHOD__, 'Insufficient permissions', [] );
        }

        // get matching rop3 row for JP
        $data = self::get_jp_source();
        $columns = $data[0];
        $rop3_row = '';
        foreach ( $data as $row ) {
            if ( $row[4] == $rop3 && $row[2] === $country ) {
                $rop3_row = $row;
                break;
            }
        }
        if ( empty( $rop3_row ) || ! is_array( $rop3_row ) ) {
            return [
                    'status' => 'Fail',
                    'message' => 'ROP3 number not found in JP data.'
            ];
        }

        // get current people groups
        // check for duplicate and return fail install because of duplicate.
        global $wpdb;
        $duplicate = $wpdb->get_var( $wpdb->prepare( "
            SELECT count(meta_id)
            FROM $wpdb->postmeta
            WHERE meta_key = 'ROP3' AND
            post_id IN ( SELECT ID FROM $wpdb->posts WHERE post_type = 'peoplegroups' ) AND
            meta_value = %s",
        $rop3 ) );
        if ( $duplicate > 0 ) {
            return [
                'status' => 'Duplicate',
                'message' => 'Duplicate found. Already installed.'
            ];
        }

        if ( ! isset( $rop3_row[4] ) ) {
            return [
                'status' => 'Fail',
                'message' => 'ROP3 title not found.',
            ];
        }

        // if no duplicate, then install full people group
        $post = [
              'post_title' => $rop3_row[5] . ' (' . $rop3_row[2] . ' | ' . $rop3_row[5] . ')',
              'post_type' => 'peoplegroups',
              'post_status' => 'publish',
              'comment_status' => 'closed',
              'ping_status' => 'closed',
        ];
        foreach ( $rop3_row as $key => $value ) {
            $post['meta_input']['jp_'.$columns[$key]] = $value;
        }

        $post_id = wp_insert_post( $post );

        // return success
        if ( ! is_wp_error( $post_id ) ) {
            return [
                'status' => 'Success',
                'message' => 'New people group has been added! ( <a href="'.admin_url() . 'post.php?post=' . $post_id . '&action=edit">View new record</a> )',
            ];
        } else {
            return [
                'status' => 'Fail',
                'message' => 'Unable to insert ' . $rop3_row[4],
            ];
        }
    }

    /**
     * Update current people group
     *
     * @param $rop3
     * @param $country
     * @param $post_id
     *
     * @return array|WP_Error
     */
    public static function link_or_update( $rop3, $country, $post_id ) {
        if ( ! current_user_can( 'manage_dt' ) ) {
            return new WP_Error( __METHOD__, 'Insufficient permissions', [] );
        }

        // get matching rop3 row for JP
        $data = self::get_jp_source();
        $columns = $data[0];
        $rop3_row = '';
        foreach ( $data as $row ) {
            if ( $row[3] == $rop3 && $row[1] === $country ) {
                $rop3_row = $row;
                break;
            }
        }
        if ( empty( $rop3_row ) || ! is_array( $rop3_row ) ) {
            return [
                'status' => 'Fail',
                'message' => 'ROP3 number not found in JP data.'
            ];
        }

        // get matching IMB data
        $imb_data = self::get_imb_source();
        $imb_columns = $imb_data[0];
        $imb_rop3_row = '';
        foreach ( $imb_data as $imb_row ) {
            if ( $imb_row[32] == $rop3 && $imb_row[5] === $country ) {
                $imb_rop3_row = $imb_row;
                break;
            }
        }

        // remove previous metadata
        global $wpdb;
        $wpdb->delete( $wpdb->postmeta, [ 'post_id' => $post_id ] );

        // if no duplicate, then install full people group
        $post = [
            'ID' => $post_id,
            'post_status' => 'publish',
            'comment_status' => 'closed',
            'ping_status' => 'closed',
        ];
        foreach ( $rop3_row as $key => $value ) {
            $post['meta_input']['jp_'.$columns[$key]] = $value;
        }
        if ( ! empty( $imb_rop3_row ) ) { // adds only if match is found
            foreach ( $imb_rop3_row as $imb_key => $imb_value ) {
                $post['meta_input']['imb_'.$imb_columns[$imb_key]] = $imb_value;
            }
        }
        $post_id = wp_update_post( $post );

        // return success
        if ( ! is_wp_error( $post_id ) ) {
            return [
                'status' => 'Success',
                'message' => 'The current people group data has been updated with this info! <a href="">Refresh to see data</a>',
            ];
        } else {
            return [
                'status' => 'Fail',
                'message' => 'Unable to update ' . $rop3_row[4],
            ];
        }
    }


    public static function admin_tab_table() {
        ?>
        <table class="widefat">
            <thead>
                <tr>
                    <th colspan="2">Unreached</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <button id="install_unreached" class="button" type="button">Install All Unreached People Groups (7000+)</button>
                        <div id="results"></div>
                    </td>
                </tr>
            </tbody>
        </table>
        <script>
            jQuery(document).ready(function(){
                jQuery('#install_unreached').on('click', function(e){
                    jQuery('#install_unreached').prop('disabled', true )
                    let results = jQuery('#results')

                    results.append(`Loading 7459 Unreached People Groups (3-9 minutes, don't close this window.) <span class="loading-spinner active"></span><br>`)

                    /* build */
                    window.offsets = []
                    let total = 7433
                    let i = 0
                    while( i < total ){
                        window.offsets.push({ start: i, end: i + 200 })
                        i = i + 200
                    }

                    /* set interval */
                    window.yint = 0
                    // window.xint = setInterval(() => {
                    //     if ( window.yint >= window.offsets.length ){
                    //         clearInterval();
                    //         return
                    //     }
                    //     send_offset()
                    //     window.yint++
                    // }, 5000);
                    send_offset()

                    function send_offset(){
                        results.append(`Loading ${window.yint} <span class="loading-spinner active" id="load${window.yint}"></span><br>`)

                        jQuery.ajax({
                            type: "POST",
                            data: JSON.stringify({ action:'unreached_json', id: window.yint,  start: window.offsets[window.yint].start, end: window.offsets[window.yint].end }),
                            contentType: "application/json; charset=utf-8",
                            dataType: "json",
                            url: '<?php echo esc_url_raw( rest_url() ) ?>dt/v1/people-groups/install',
                            beforeSend: function(xhr) {
                                xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce( 'wp_rest' ) ?>');
                            },
                        })
                        .done(function (data) {
                            let active = jQuery('.active')
                            jQuery('#load'+data).remove()
                            if ( 37 === data ){
                                active.removeClass('active')
                                results.append('Finished!')
                            }
                        })
                        .fail(function (err) {
                            console.log("error");
                            console.log(err);
                        })
                    }
                })
            })
        </script>

        <?php
    }
}

class Disciple_Tools_People_Groups_Endpoints
{
    private $namespace;

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
    }

    public function add_api_routes() {
        $this->namespace = "dt/v1";
        register_rest_route(
            $this->namespace, '/people-groups/install', [
                'methods'  => 'POST',
                'callback' => [ $this, 'install' ],
            ]
        );
        register_rest_route(
            $this->namespace, '/people-groups/compact', [
                'methods'  => 'GET',
                'callback' => [ $this, 'get_people_groups_compact' ],
            ]
        );
    }

    public function install( WP_REST_Request $request ) {
        if ( !current_user_can( "access_contacts" )){
            return new WP_Error( __FUNCTION__, "You do not have permission for this", [ 'status' => 403 ] );
        }
        $params = $request->get_params();

        switch ( $params['action'] ) {
            case 'unreached_json':
                return $this->add_unreached_json();
            case 'unreached';
                return $this->add_unreached( $params['start'], $params['end'], $params['id'] );
            default:
                return [];
        }
    }

    public function add_unreached( $start, $end, $id ){
        global $wpdb;
        $data = [];
        $list = Disciple_Tools_People_Groups_Admin::get_jp_unreached();

        $installed = $wpdb->get_results( "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = 'pg_unique_key';", ARRAY_A );
        $keys = [];
        foreach ( $installed as $item ) {
            $keys[$item['meta_value']] = $item['post_id'];
        }

        foreach ( $list as $index => $row ) {
            if ( $index >= $start && $index <= $end ) {

                $unique_key = $row[1] . '_'. $row[3] . '_'. $row[4]; // rog3+peopleid3+rop3
                if ( isset( $keys[$unique_key] ) ) {
                    continue;
                }

                $title = esc_sql( $row[5] . ' ('.$row[4]. ', '. $row[2].')' );

                $fields = [
                    "title" => $title,
                    "status" => "inactive",
                    'rop3' => $row[4],
                    'country' => $row[1],
                    'pg_unique_key' => $unique_key,
                ];
                $value = [
                    "values" => [
                        [
                            'lng' => $row[8],
                            'lat' => $row[7],
                            'level' => $row[9],
                            'label' => $row[10],
                            'grid_id' => $row[11]
                        ]
                    ]
                ];
                $fields['location_grid_meta'] = $value;

                $data[] = DT_Posts::create_post( 'peoplegroups', $fields, true, false );

            }
            if ( $index > $end ){
                break;
            }
        }
        return $id;
    }

    public function add_unreached_json() {
        global $wpdb;
        $list = Disciple_Tools_People_Groups_Admin::get_jp_unreached_json();
        $base_user = dt_get_base_user( true );

        foreach ( $list as $key => $value ) {

            $id = wp_insert_post(
                [
                    'post_title' => $value['name'] . ' ('. $value['rop3'] .')',
                    'post_type' => 'peoplegroups',
                ]
            );


            if ( ! $id ) {
                continue;
            }
            dt_write_log( $id );
            $wpdb->insert(
                $wpdb->postmeta,
                [
                    'post_id' => $id,
                    'meta_key' => 'rop3',
                    'meta_value' => $value['rop3'],
                ]
            );
            $wpdb->insert(
                $wpdb->postmeta,
                [
                    'post_id' => $id,
                    'meta_key' => 'peopleid3',
                    'meta_value' => $value['peopleid3'],
                ]
            );
            $wpdb->insert(
                $wpdb->postmeta,
                [
                    'post_id' => $id,
                    'meta_key' => 'status',
                    'meta_value' => 'inactive',
                ]
            );
            $wpdb->insert(
                $wpdb->postmeta,
                [
                    'post_id' => $id,
                    'meta_key' => 'assigned_to',
                    'meta_value' => 'user-' . $base_user,
                ]
            );

            foreach ( $value['locations'] as $locations ) {
                $wpdb->insert(
                    $wpdb->postmeta,
                    [
                        'post_id' => $id,
                        'meta_key' => 'location_grid',
                        'meta_value' => $locations['grid_id']
                    ]
                );

                // create the location grid meta record
                $location_grid_id = $wpdb->insert_id;
                $wpdb->insert(
                    $wpdb->dt_location_grid_meta,
                    [
                        'post_id' => $id,
                        'post_type' => 'peoplegroups',
                        'postmeta_id_location_grid' => $location_grid_id,
                        'grid_id' => $locations['grid_id'],
                        'lng' => $locations['lng'],
                        'lat' => $locations['lat'],
                        'level' => $locations['level'],
                        'source' => 'jp',
                        'label' => $locations['label']
                    ]
                );

                // create the location grid meta postmeta record
                $location_grid_meta_id = $wpdb->insert_id;
                ;
                $wpdb->insert(
                    $wpdb->postmeta,
                    [
                        'post_id' => $id,
                        'meta_key' => 'location_grid_meta',
                        'meta_value' => $location_grid_meta_id
                    ]
                );

                $data[] = $id;
            } // foreach
        }
        return $data;
    }

    /**
     * @param \WP_REST_Request $request
     *
     * @return array|WP_Error
     */
    public function get_people_groups_compact( WP_REST_Request $request ) {
        if ( !current_user_can( "access_contacts" )){
            return new WP_Error( __FUNCTION__, "You do not have permission for this", [ 'status' => 403 ] );
        }

        $params = $request->get_params();
        $search = "";
        if ( isset( $params['s'] ) ) {
            $search = $params['s'];
        }

        $locale = get_user_locale();
        $query_args = [
            'post_type' => 'peoplegroups',
            'orderby'   => 'title',
            'order' => 'ASC',
            'nopaging'  => true,
            's'         => $search,
        ];
        $query = new WP_Query( $query_args );

        $list = [];
        foreach ( $query->posts as $post ) {
            $translation = get_post_meta( $post->ID, $locale, true );
            if ($translation !== "") {
                $label = $translation;
            } else {
                $label = $post->post_title;
            }

            $list[] = [
                "ID" => $post->ID,
                "name" => $post->post_title,
                "label" => $label
            ];
        }
        $meta_query_args = [
            'post_type' => 'peoplegroups',
            'orderby'   => 'title',
            'order' => 'ASC',
            'nopaging'  => true,
            'meta_query' => array(
                array(
                    'key' => $locale,
                    'value' => $search,
                    'compare' => 'LIKE'
                )
            ),
        ];

        $meta_query = new WP_Query( $meta_query_args );
        foreach ( $meta_query->posts as $post ) {
            $translation = get_post_meta( $post->ID, $locale, true );
            if ($translation !== "") {
                $label = $translation;
            } else {
                $label = $post->post_title;
            }
            $list[] = [
                "ID" => $post->ID,
                "name" => $post->post_title,
                "label" => $label
            ];
        }

        $total_found_posts = $query->found_posts + $meta_query->found_posts;

        $list = array_intersect_key($list, array_unique( array_map( function ( $el ) {
            return $el['ID'];
        }, $list ) ) );

        return [
            "total" => $total_found_posts,
            "posts" => $list
        ];
    }

}
Disciple_Tools_People_Groups_Endpoints::instance();
