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
            SELECT ID
            FROM $wpdb->posts p
            WHERE post_type = 'peoplegroups'
            AND p.ID NOT IN (
                SELECT p1.ID
                FROM $wpdb->posts p1
                JOIN $wpdb->postmeta pm1 ON p1.ID=pm1.post_id AND meta_key = 'status'
                WHERE p1.post_type = 'peoplegroups'
             );", ARRAY_A);

        if ( ! empty( $pg_not_upgraded ) ) {
            foreach( $pg_not_upgraded as $id ) {
                update_post_meta( $id['ID'], 'status', 'inactive' );

                $rop3 = get_post_meta( $id['ID'], 'jp_ROP3', true );
                if ( ! empty( $rop3 ) ){
                    update_post_meta( $id['ID'], 'rop3', $rop3 );
                }
            }
        }

        $pg_not_assigned = $wpdb->get_results("
            SELECT ID
            FROM $wpdb->posts p
            WHERE post_type = 'peoplegroups'
            AND p.ID NOT IN (
                SELECT p1.ID
                FROM $wpdb->posts p1
                JOIN $wpdb->postmeta pm1 ON p1.ID=pm1.post_id AND meta_key = 'assigned_to'
                WHERE p1.post_type = 'peoplegroups'
             );", ARRAY_A);

        if ( ! empty( $pg_not_assigned ) ) {
            $base_user = dt_get_base_user();
            foreach( $pg_not_assigned as $id ) {
                update_post_meta( $id['ID'], 'assigned_to', 'user-' . $base_user->ID );
                DT_Posts::add_shared( "peoplegroups", (int) $id['ID'], (int) $base_user->ID, null, false, false, false );
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
