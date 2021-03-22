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
                    'step' => 'G0',
                    'title' => 'Present',
                    'description' => 'Establishing presence in the people group.'
                ],
                'g0.1' => [
                    'step' => 'G0.1',
                    'title' => 'Apostolic effort in residence',
                    'description' => ''
                ],

                'g0.2' => [
                    'step' => 'G0.2',
                    'title' => 'Commitment to work in local language',
                    'description' => ''
                ],

                'g0.3' => [
                    'step' => 'G0.3',
                    'title' => 'Commitment to long-term ministry',
                    'description' => ''
                ],
                'g1' => [
                    'step' => 'G1',
                    'title' => 'Moving purposefully',
                    'description' => 'Team on site trying to consistently establish NEW 1st generation believers & churches.'
                ],
                'g1.1'
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

                ?>
                <div class="grid-x">
                    <div class="cell">
                        <button type="button" class="button hollow small" style="margin-bottom: 2px;">&#10003;</button> <strong>(G0) Present</strong>
                    </div>
                    <div class="cell">
                        <button type="button" class="button hollow small" style="margin-bottom: 2px;;">&#10003;</button> 0.1 Apostolic effort in residence
                    </div>
                    <div class="cell">
                        <button type="button" class="button hollow small" style="margin-bottom: 2px;;">&#10003;</button> 0.2 Commitment to work in local language
                    </div>
                    <div class="cell">
                        <button type="button" class="button hollow small" style="margin-bottom: 2px;">&#10003;</button> 0.3 Commitment to long-term ministry
                    </div>
                    <div class="cell">
                        <button type="button" class="button hollow small" style="margin-bottom: 2px;">&#10003;</button> 0.4 Commitment to sowing with CPM vision
                    </div>
                    <div class="cell">
                        <button type="button" class="button hollow small" style="margin-bottom: 2px;">&#10003;</button> <strong>(G1) Moving purposefully</strong>
                    </div>
                    <div class="cell">
                        <em>Team on site trying to consistently establish NEW 1st generation believers & churches.</em>
                    </div>

                    <div class="cell">
                        <button type="button" class="button hollow small" style="margin-bottom: 2px;">&#10003;</button> 1.1 Field 1/Field 2 activity but no results
                    </div>
                    <div class="cell">
                        <button type="button" class="button hollow small" style="margin-bottom: 2px;">&#10003;</button> 1.2 Have some new G1 believers
                    </div>
                    <div class="cell">
                        <button type="button" class="button hollow small" style="margin-bottom: 2px;">&#10003;</button> 1.3 Have some new G1 believers
                    </div>
                    <div class="cell">
                        <button type="button" class="button hollow small" style="margin-bottom: 2px;">&#10003;</button> 1.4 have a consistent G1 believers
                    </div>
                    <div class="cell">
                        <button type="button" class="button hollow small" style="margin-bottom: 2px;">&#10003;</button> 1.5 Have consistent G1 believers
                    </div>
                    <div class="cell">
                        <button type="button" class="button hollow small" style="margin-bottom: 2px;">&#10003;</button> 1.6 have one or some G1 churches
                    </div>
                    <div class="cell">
                        <button type="button" class="button hollow small" style="margin-bottom: 2px;">&#10003;</button> 1.7 have one or some G1 churches
                    </div>
                    <div class="cell">
                        <button type="button" class="button hollow small" style="margin-bottom: 2px;">&#10003;</button> 1.8 Have several G1 churches
                    </div>
                    <div class="cell">
                        <button type="button" class="button hollow small" style="margin-bottom: 2px;">&#10003;</button> 1.9 Close to G2 churches
                    </div>
                    <div class="cell">
                        <button type="button" class="button hollow small" style="margin-bottom: 2px;">&#10003;</button> <strong>(G2) Focused</strong>
                    </div>
                    <div class="cell">
                        <em>Some 2nd gen churches (G1 believers started them).</em>
                    </div>
                    <div class="cell">
                        <button type="button" class="button hollow small" style="margin-bottom: 2px;">&#10003;</button> <strong>(G3) Breakthough</strong>
                    </div>
                    <div class="cell">
                        <em>Consistent G2 & some G3 churches.</em>
                    </div>
                    <div class="cell">
                        <button type="button" class="button hollow small" style="margin-bottom: 2px;">&#10003;</button> <strong>(G4) Emerging CPM</strong>
                    </div>
                    <div class="cell">
                        <em>Consistent G3 & some G4 churches.</em>
                    </div>
                    <div class="cell">
                        <button type="button" class="button hollow small" style="margin-bottom: 2px;">&#10003;</button> <strong>(G5) CPM</strong>
                    </div>
                    <div class="cell">
                        <em>Consistent 4th generation churches; multiple streams.</em>
                    </div>
                    <div class="cell">
                        <button type="button" class="button hollow small" style="margin-bottom: 2px;">&#10003;</button> <strong>(G6) Sustained CPM</strong>
                    </div>
                    <div class="cell">
                        <em>Visionary, indigenous leadership leding the movement with little/no need for outsiders. Stood
                            test of time.</em>
                    </div>
                    <div class="cell">
                        <button type="button" class="button hollow small" style="margin-bottom: 2px;">&#10003;</button> <strong>(G7) Multiplying CPMs</strong>
                    </div>
                    <div class="cell">
                        <em>Catalyzing new CPMs in other unreached peoples and places.</em>
                    </div>
                </div>
                <?php
            }

        } // post type
    }

}


