<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class Disciple_Tools_Migration_0041
 *
 * Adds a status to all people groups
 *
 */
class Disciple_Tools_Migration_0041 extends Disciple_Tools_Migration {
    public function up() {
        global $wpdb;

        $pg_not_upgraded = $wpdb->get_results("
            SELECT p.ID, pm1.meta_value as rop3, pm2.meta_value as country, CONCAT(pm2.meta_value, '_', pm3.meta_value, '_', pm1.meta_value) as pg_unique_key
                FROM $wpdb->posts p
                LEFT JOIN $wpdb->postmeta pm1 ON p.ID=pm1.post_id AND pm1.meta_key = 'jp_rop3'
                LEFT JOIN $wpdb->postmeta pm2 ON p.ID=pm2.post_id AND pm2.meta_key = 'jp_rog3'
                LEFT JOIN $wpdb->postmeta pm3 ON p.ID=pm3.post_id AND pm3.meta_key = 'jp_peopleid3'
                WHERE post_type = 'peoplegroups';
             ", ARRAY_A);

        $base_user = dt_get_base_user( true );

        if ( ! empty( $pg_not_upgraded ) ) {
            foreach ( $pg_not_upgraded as $row ) {
                $wpdb->insert(
                    $wpdb->postmeta,
                    [
                        'post_id' => $row['ID'],
                        'meta_key' => 'status',
                        'meta_value' => 'inactive'
                    ]
                );
                $wpdb->insert(
                    $wpdb->postmeta,
                    [
                        'post_id' => $row['ID'],
                        'meta_key' => 'rop3',
                        'meta_value' => $row['rop3']
                    ]
                );
                $wpdb->insert(
                    $wpdb->postmeta,
                    [
                        'post_id' => $row['ID'],
                        'meta_key' => 'country',
                        'meta_value' => $row['country']
                    ]
                );
                $wpdb->insert(
                    $wpdb->postmeta,
                    [
                        'post_id' => $row['ID'],
                        'meta_key' => 'pg_unique_key',
                        'meta_value' => $row['pg_unique_key']
                    ]
                );
                $wpdb->insert(
                    $wpdb->postmeta,
                    [
                        'post_id' => $row['ID'],
                        'meta_key' => 'assigned_to',
                        'meta_value' => 'user-' . $base_user
                    ]
                );

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
