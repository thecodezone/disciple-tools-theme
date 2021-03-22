<?php
/**
 * Functions class
 */


class Disciple_Tools_User_Dashboard
{
    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    private $version = 1;
    private $context = "dt-dashboard";
    private $namespace;

    public function __construct() {
        $this->namespace = $this->context . "/v" . intval( $this->version );
        add_filter( 'dt_front_page', [ $this, 'front_page' ] );

        add_filter( 'desktop_navbar_menu_options', [ $this, 'nav_menu' ], 10, 1 );
        add_filter( 'off_canvas_menu_options', [ $this, 'nav_menu' ] );

        $url_path = dt_get_url_path();
        add_action( "init", [ $this, 'my_theme_redirect' ] );
        if ( strpos( $url_path, 'dashboard' ) !== false ) {
            add_action( 'wp_enqueue_scripts', [ $this, 'scripts' ], 99 );
        }



        new Disciple_Tools_Dashboard_Tile( "tile1", "TILE !", 'dashboard', 'row1' );
        add_action( 'dt_dashboard_tiles', [ $this, 'dt_dashboard_tiles' ], 10, 2 );
    }

    public function dt_dashboard_tiles( $dashboard_page, $row_id ){
        if ( $row_id === "row1" ){
            ?>
            <div class="bordered-box dashboard-tile">
                <div style="text-align: center">
                    <span class="card-title"><?php esc_html_e( " Tile 1", 'disciple-tools-dashboard' ) ?></span>
                </div>
            </div>
            <div class="bordered-box dashboard-tile">
                <div style="text-align: center">
                    <span class="card-title"><?php esc_html_e( " Tile 2", 'disciple-tools-dashboard' ) ?></span>
                </div>
            </div>

            <?php
        }
    }

    public function my_theme_redirect() {
        $url = dt_get_url_path();
        if ( strpos( $url, "dashboard" ) !== false ){
            $template = locate_template( "template-dashboard.php", true );
            if ( $template ){
                exit();
            }
        }
    }

    public function scripts() {

//        wp_enqueue_style( 'dashboard-css', plugin_dir_url( __FILE__ ) . '/style.css', array(), filemtime( plugin_dir_path( __FILE__ ) . 'style.css' ) );

        wp_register_script( 'amcharts-core', 'https://www.amcharts.com/lib/4/core.js', false, '4' );
        wp_register_script( 'amcharts-charts', 'https://www.amcharts.com/lib/4/charts.js', false, '4' );
        wp_register_script( 'amcharts-animated', 'https://www.amcharts.com/lib/4/themes/animated.js', false, '4' );
//        wp_enqueue_script( 'dt-dashboard', get_template_directory_uri() . '/dt-dashboard/dashboard.js', [
//            'jquery',
//            'jquery-ui',
//            'lodash',
//            'amcharts-core',
//            'amcharts-charts',
//            'amcharts-animated',
//            'moment'
//        ], filemtime( get_template_directory() . '/dt-dashboard/dashboard.js' ), true );
//        wp_localize_script(
//            'dt-dashboard', 'dashboard_settings', array(
//                'root'                  => esc_url_raw( rest_url() ),
//                'site_url'              => get_site_url(),
//                'nonce'                 => wp_create_nonce( 'wp_rest' ),
//                'current_user_login'    => wp_get_current_user()->user_login,
//                'current_user_id'       => get_current_user_id(),
//                'template_dir'          => get_template_directory_uri(),
////                'translations'          => DT_Dashboard_Plugin_Endpoints::instance()->translations(),
////                'data'                  => DT_Dashboard_Plugin_Endpoints::instance()->get_data(),
//                'workload_status'       => get_user_option( 'workload_status', get_current_user_id() ),
//                'workload_status_options' => dt_get_site_custom_lists()["user_workload_status"] ?? []
//            )
//        );
    }

    public function front_page( $page ){
        return site_url( '/dashboard/' );
    }

    public function nav_menu( $tabs ){
        $tabs['dashboard'] = [
            "link" => site_url( '/dashboard/' ),
            "label" => __( "Dashboard", "disciple-tools-dashboard" )
        ];
        return $tabs;

    }




}
new Disciple_Tools_User_Dashboard();


class Disciple_Tools_Dashboard_Tile {
    public $tile_id;
    public $dashboard_page;
    public $dashboard_row;

    public function __construct( $tile_id, $tile_name, $dashboard_page, $dashboard_row ){
        $this->tile_id = $tile_id;
        $this->dashboard_page = $dashboard_page;
        $this->dashboard_row = $dashboard_row;

//        add_action( 'dt_dashboard_tiles', [ $this, 'dt_dashboard_tiles' ], 10, 2 );
        add_filter( 'dt_dashboard_rows', [ $this, 'dt_dashboard_rows' ], 10, 2 );
    }

    public function dt_dashboard_rows( $rows, $dashboard_page ){
        $rows[] = [
            "id" => "row1",
        ];
        $rows[] = [
            "id" => "row2",
        ];
        return $rows;
    }

    public function dt_dashboard_tiles( $dashboard_page, $row_id ){
        if ( $row_id === "row1" ){
            ?>
            <div class="bordered-box dashboard-tile">
                <div style="text-align: center">
                    <span class="card-title"><?php esc_html_e( " Tile 2", 'disciple-tools-dashboard' ) ?></span>
                </div>
            </div>

            <?php
        }
    }
}
