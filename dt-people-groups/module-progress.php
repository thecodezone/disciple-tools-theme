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
        add_filter( 'dt_custom_fields_settings', [ $this, 'dt_custom_fields_settings' ], 10, 2 );
        add_filter( 'dt_details_additional_tiles', [ $this, 'dt_details_additional_tiles' ], 10, 2 );
        add_action( 'dt_details_additional_section', [ $this, 'dt_details_additional_section' ], 20, 2 );

    }

    public function dt_custom_fields_settings( $fields, $post_type ){
        if ( $post_type === $this->post_type ){
            $movement = [
                'g0' => [
                    'label' => '(G0) Presence',
                    'description' => 'Establishing presence in the people group.'
                ],
                'g0_1' => [
                    'label' => '- 0.1 Apostolic effort in residence',
                    'description' => ''
                ],
                'g0_2' => [
                    'label' => '- 0.2 Commitment to work in local language',
                    'description' => ''
                ],
                'g0_3' => [
                    'label' => '- 0.3 Commitment to long-term ministry',
                    'description' => ''
                ],
                'g0_4' => [
                    'label' => '- 0.4 Commitment to sowing with CPM vision',
                    'description' => ''
                ],
                'g1' => [
                    'label' => '(G1) Moving purposefully',
                    'description' => 'Team on site trying to consistently establish NEW 1st generation believers & churches.'
                ],
                'g1_1' => [
                    'label' => '- 1.1 Field 1/Field 2 activity but no results',
                    'description' => ''
                ],
                'g1_2' => [
                    'label' => '- 1.2 Have a new G1 believer',
                    'description' => ''
                ],
                'g1_3' => [
                    'label' => '- 1.3 Have some new G1 believers',
                    'description' => ''
                ],
                'g1_4' => [
                    'label' => '- 1.4 Have consistent G1 believers',
                    'description' => ''
                ],
                'g1_5' => [
                    'label' => '- 1.5 Have many consistent G1 believers',
                    'description' => ''
                ],
                'g1_6' => [
                    'label' => '- 1.6 Have one G1 church',
                    'description' => ''
                ],
                'g1_7' => [
                    'label' => '- 1.7 Have some G1 churches',
                    'description' => ''
                ],
                'g1_8' => [
                    'label' => '- 1.8 Have several G1 churches',
                    'description' => ''
                ],
                'g1_9' => [
                    'label' => '- 1.9 Close to G2 churches',
                    'description' => ''
                ],
                'g2' => [
                    'label' => '(G2) Focused',
                    'description' => 'Some 2nd gen churches (G1 believers started them).'
                ],
                'g3' => [
                    'label' => '(G3) Breakthough',
                    'description' => 'Consistent G2 & some G3 churches.'
                ],
                'g4' => [
                    'label' => '(G4) Emerging CPM',
                    'description' => 'Consistent G3 & some G4 churches.'
                ],
                'g5' => [
                    'label' => '(G5) CPM',
                    'description' => 'Consistent 4th generation churches; multiple streams.'
                ],
                'g6' => [
                    'label' => '(G6) Sustained CPM',
                    'description' => 'Visionary, indigenous leadership leading the movement with little/no need for outsiders. Stood test of time.'
                ],
                'g7' => [
                    'label' => '(G7) Multiplying CPMs',
                    'description' => 'Catalyzing new CPMs in other unreached peoples and places.'
                ],
            ];


            $fields['pg_progress'] = [
                'name'        => __( 'Progress', 'disciple_tools' ),
                'description' => _x( 'Set the current status.', 'field description', 'disciple_tools' ),
                'type'        => 'multi_select',
                'default'     => $movement,
                'tile' => 'progress',
                'icon' => get_template_directory_uri() . '/dt-assets/images/status.svg',
                "default_color" => "#366184",
                "hidden" => true,
                "custom_display" => true,
            ];

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

            if ( 'progress' === $section ) {
                $post_fields = DT_Posts::get_post_field_settings( $post_type );
                $post = DT_Posts::get_post( $post_type, get_the_ID() );
                ?>
                <div class="small button-group" style="display: inline-block">
                    <?php foreach ( $post_fields['pg_progress']["default"] as $option_key => $option_value ): ?>
                    <div>
                        <?php
                        $class = ( in_array( $option_key, $post['pg_progress'] ?? [] ) ) ?
                            "selected-select-button" : "empty-select-button"; ?>
                        <button id="<?php echo esc_html( $option_key ) ?>" type="button" data-field-key="pg_progress"
                                class="dt_multi_select <?php echo esc_html( $class ) ?> select-button button" style="text-align:left;">
                            &#10003;
                        </button>
                        <?php echo esc_html( $post_fields['pg_progress']["default"][$option_key]["label"] ) ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php
            }

         // post type
        }
    }

}


