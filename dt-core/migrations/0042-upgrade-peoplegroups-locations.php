<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class Disciple_Tools_Migration_0041
 *
 * Adds a status to all people groups
 *
 */
class Disciple_Tools_Migration_0042 extends Disciple_Tools_Migration {
    public function up() {
        global $wpdb;

        $pg_not_upgraded = $wpdb->get_results("
            SELECT p.ID, pm1.meta_value as pg_unique_key
                FROM $wpdb->posts p
                JOIN $wpdb->postmeta pm1 ON p.ID=pm1.post_id AND pm1.meta_key = 'pg_unique_key'
                WHERE post_type = 'peoplegroups';
             ", ARRAY_A);


        if ( ! empty( $pg_not_upgraded ) ) {

            $list = Disciple_Tools_People_Groups_Admin::get_jp_source();
            if ( empty( $list ) ){
                return;
            }

            foreach ( $pg_not_upgraded as $record ) {
                if ( isset( $list[$record['pg_unique_key'] ] ) ) {
                    $row = $list[$record['pg_unique_key'] ];

                    // create the location grid record
                    $wpdb->insert(
                        $wpdb->postmeta,
                        [
                            'post_id' => $record['ID'],
                            'meta_key' => 'location_grid',
                            'meta_value' => $row[11]
                        ]
                    );

                    // create the location grid meta record
                    $location_grid_id = $wpdb->insert_id;
                    $wpdb->insert(
                        $wpdb->dt_location_grid_meta,
                        [
                            'post_id' => $record['ID'],
                            'post_type' => 'peoplegroups',
                            'postmeta_id_location_grid' => $location_grid_id,
                            'grid_id' => $row[11],
                            'lng' => $row[8],
                            'lat' => $row[7],
                            'level' => $row[9],
                            'source' => 'jp',
                            'label' => $row[10]
                        ]
                    );

                    // create the location grid meta postmeta record
                    $location_grid_meta_id = $wpdb->insert_id;
                    ;
                    $wpdb->insert(
                        $wpdb->postmeta,
                        [
                            'post_id' => $record['ID'],
                            'meta_key' => 'location_grid_meta',
                            'meta_value' => $location_grid_meta_id
                        ]
                    );

                }
            }
        }
    }

    public function down() {
    }

    public function test() {
    }

    public function get_expected_tables(): array {
        return [];
    }
}
