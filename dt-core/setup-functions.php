<?php
/**
 * This file is only called from the wp-admin and so won't be run on every request.
 */


/**
 * make sure roles and permissions conform to the values set by the modules
 */
add_action( 'init', "dt_setup_roles_and_permissions" );
add_filter( 'dt_set_roles_and_permissions', "dt_set_custom_roles_and_permissions", 11, 1 );


function dt_setup_roles_and_permissions(){
    $default_role = get_option( 'default_role' );
    if ( $default_role === "subscriber" || empty( $default_role ) ){
        update_option( 'default_role', 'multiplier' );
    }

    $expected_roles_options = get_option( 'dt_options_roles_and_permissions', [] );
    $expected_roles = apply_filters( 'dt_set_roles_and_permissions', [] );
    $expected_roles = dt_array_merge_recursive_distinct( $expected_roles, $expected_roles_options );
    $dt_roles = array_map( function ( $a ){
        return array_keys( $a["permissions"] );
    }, $expected_roles );
    $dt_permissions = array_merge( ...array_values( $dt_roles ) );

    $role_keys = dt_multi_role_get_role_slugs();
    foreach ( $expected_roles as $role_key => $role_values ){
        if ( !in_array( $role_key, $role_keys, true ) ){
            add_role( $role_key, $role_values["label"] ?? "", $role_values["permissions"] ?? [] );
        }
    }
    //get all the roles
    $roles = dt_multi_role_get_roles();

    foreach ( $roles as $role_key => $role_value ){
        if ( in_array( $role_key, [ "registered" ] ) ){
            continue;
        }
        $role = get_role( $role_key );
        if ( $role && isset( $expected_roles[$role_key] ) ){
            //add permissions to roles
            foreach ( $expected_roles[$role_key]["permissions"] as $cap_key => $cap_grant ){
                if ( empty( $cap_key ) ){
                    continue;
                }
                if ( !isset( $role_value->caps[$cap_key] ) ){
                    $role->add_cap( $cap_key, $cap_grant );
                } else if ( $role_value->caps[$cap_key] !== $cap_grant ){
                    if ( $cap_grant === false ){
                        $role->remove_cap( $cap_key );
                    } else {
                        $role->add_cap( $cap_key );
                    }
                }
            }
            //remove permissions if they are set by the $expected_roles
            foreach ( $role->capabilities as $cap_key => $cap_grant ){
                if ( $cap_grant === true && !isset( $expected_roles[$role_key]["permissions"][$cap_key] ) ){
                    if ( in_array( $role_key, [ "administrator" ], true ) && !in_array( $cap_key, $dt_permissions, true ) ){
                        continue; //don't remove a non D.T cap from the administrator
                    }
                    $role->remove_cap( $cap_key );
                }
            }
        } else {
            if ( !in_array( $role_key, [ "administrator" ], true ) ){
                // remove roles that are no longer defined.
                remove_role( $role_key );
            }
        }
    }
}

function dt_set_custom_roles_and_permissions( $roles ) {
    global $wpdb;

    $custom_roles = $wpdb->get_results( "SELECT * FROM {$wpdb->dt_roles}" );
    foreach ( $custom_roles as $role ) {
        $roles[$role->role_slug] = [
            'label' => $role->role_label,
            'permissions' => json_decode( $role->role_capabilities, true ),
            'description' => $role->role_description,
            'custom' => true
        ];
    }

    return $roles;
}
