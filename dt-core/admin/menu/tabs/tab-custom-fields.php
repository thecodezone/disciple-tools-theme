<?php

/**
 * Disciple Tools
 *
 * @class      Disciple_Tools_Tab_Custom_Fields
 * @version    0.1.0
 * @since      0.1.0
 * @package    Disciple_Tools
 * @author     Chasm.Solutions & Kingdom.Training
 */

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class Disciple_Tools_Tab_Custom_Fields
 */
class Disciple_Tools_Tab_Custom_Fields extends Disciple_Tools_Abstract_Menu_Base
{

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    /**
     * Constructor function.
     *
     * @access  public
     * @since   0.1.0
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_submenu' ], 99 );
        add_action( 'dt_settings_tab_menu', [ $this, 'add_tab' ], 10, 1 );
        add_action( 'dt_settings_tab_content', [ $this, 'content' ], 99, 1 );

        parent::__construct();
    } // End __construct()

    public function add_submenu() {
        add_submenu_page( 'dt_options', __( 'Custom Fields', 'disciple_tools' ), __( 'Custom Fields', 'disciple_tools' ), 'manage_dt', 'dt_options&tab=custom-fields', [ 'Disciple_Tools_Settings_Menu', 'content' ] );
    }

    public function add_tab( $tab ) {
        ?>
        <a href="<?php echo esc_url( admin_url() ) ?>admin.php?page=dt_options&tab=custom-fields"
           class="nav-tab <?php echo esc_html( $tab == 'custom-fields' ? 'nav-tab-active' : '' ) ?>">
            <?php echo esc_html__( 'Custom Fields' ) ?>
        </a>
        <?php
    }

    private function get_post_fields( $post_type ){
        if ( $post_type === "groups" ){
            return Disciple_Tools_Groups_Post_Type::instance()->get_custom_fields_settings( null, null, true );
        } elseif ( $post_type === "contacts" ){
            return Disciple_Tools_Contact_Post_Type::instance()->get_custom_fields_settings( null, null, true );
        }
        return null;
    }

    /**
     * Packages and prints tab page
     *
     * @param $tab
     */
    public function content( $tab ) {
        if ( 'custom-fields' == $tab ) :
            $show_add_field = false;
            $field_key = false;
            $post_type = null;
            $this->template( 'begin' );

            if ( isset( $_POST['field_select_nonce'] ) ){
                if ( !wp_verify_nonce( sanitize_key( $_POST['field_select_nonce'] ), 'field_select' ) ) {
                    return;
                }
                if ( isset( $_POST["show_add_new_field"] ) ){
                    $show_add_field = true;
                } else if ( isset( $_POST["field-select"] ) ){
                    $field = explode( "_", sanitize_text_field( wp_unslash( $_POST["field-select"] ) ), 2 );
                    $field_key = $field[1];
                    $post_type = $field[0];
                }
            }


            /*
             * Process Add field
             */
            if ( isset( $_POST["new_field_type"], $_POST['field_add_nonce'] ) ){
                if ( !wp_verify_nonce( sanitize_key( $_POST['field_add_nonce'] ), 'field_add' ) ) {
                    return;
                }
                $post_submission = [];
                foreach ( $_POST as $key => $value ){
                    $post_submission[sanitize_text_field( wp_unslash( $key ) )] = sanitize_text_field( wp_unslash( $value ) );
                }
                $field_key = $this->process_add_field( $post_submission );
                if ( $field_key === false ){
                    $show_add_field = true;
                }
                $post_type = $post_submission["post_type"];
            }
            /*
             * Process Edit field
             */
            if ( isset( $_POST["field_edit_nonce"] ) ){
                if ( !wp_verify_nonce( sanitize_key( $_POST['field_edit_nonce'] ), 'field_edit' ) ) {
                    return;
                }
                $post_submission = [];
                foreach ( $_POST as $key => $value ){
                    $post_submission[sanitize_text_field( wp_unslash( $key ) )] = sanitize_text_field( wp_unslash( $value ) );
                }
                $this->process_edit_field( $post_submission );
            }

            $this->box( 'top', __( 'Add new fields or modify existing ones on Contacts or Groups', 'disciple_tools' ) );
            $this->field_select();
            $this->box( 'bottom' );


            if ( $show_add_field ){
                $this->box( 'top', __( "Add new field", 'disciple_tools' ) );
                $this->add_field();
                $this->box( 'bottom' );
            }
            if ( $field_key && $post_type ){
                $this->box( 'top', $this->get_post_fields( $post_type )[$field_key]["name"] );
                $this->edit_field( $field_key, $post_type );
                $this->box( 'bottom' );
            }

            $this->template( 'right_column' );

            $this->template( 'end' );
        endif;
    }

    private function field_select(){
        $select_options = [];
        $contact_fields = Disciple_Tools_Contact_Post_Type::instance()->get_custom_fields_settings( null, null, true );
        foreach ( $contact_fields as $contact_field_key => $contact_field_value ){
            if ( isset( $contact_field_value["customizable"] ) ){
                $select_options[] = $contact_field_key;
            }
        }
        $group_select_options = [];
        $group_fields = Disciple_Tools_Groups_Post_Type::instance()->get_custom_fields_settings( null, null, true );
        foreach ( $group_fields as $group_field_key => $group_field_value ){
            if ( isset( $group_field_value["customizable"] ) ){
                $group_select_options[] = $group_field_key;
            }
        }

        ?>
        <form method="post">
            <input type="hidden" name="field_select_nonce" id="field_select_nonce" value="<?php echo esc_attr( wp_create_nonce( 'field_select' ) ) ?>" />
            <button type="submit" class="button" name="show_add_new_field"><?php esc_html_e( "Add a new field", 'disciple_tools' ) ?></button>
            <label for="field-select"><?php esc_html_e( "Or modify an existing field", 'disciple_tools' ) ?></label>

            <label for="field-select">Contacts</label>
            <select id="field-select" name="field-select">
                <option></option>
                <option disabled>---Contact Fields---</option>
                <?php foreach ( $select_options as $option ) : ?>
                    <option value="contacts_<?php echo esc_html( $option ) ?>">
                        <?php echo esc_html( isset( $contact_fields[$option]["name"] ) ? $contact_fields[$option]["name"] : $option ) ?>
                    </option>
                <?php endforeach; ?>
                <option disabled>---Group Fields---</option>
                <?php foreach ( $group_select_options as $option ) : ?>
                    <option value="groups_<?php echo esc_html( $option ) ?>">
                        <?php echo esc_html( isset( $group_fields[$option]["name"] ) ? $group_fields[$option]["name"] : $option ) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="button" name="field_selected"><?php esc_html_e( "Select", 'disciple_tools' ) ?></button>
        </form>

    <?php }

    private function edit_field( $field_key, $post_type ){

        $post_fields = $this->get_post_fields( $post_type );
        if ( !isset( $post_fields[$field_key]["default"] ) ) {
            wp_die( 'Failed to get dt_site_custom_lists() from options table.' );
        }
        $field = $post_fields[$field_key];

        $defaults = null;
        if ( $post_type === "contacts" ){
            $defaults = Disciple_Tools_Contact_Post_Type::instance()->get_contact_fields();
        } elseif ( $post_type === "groups" ){
            $defaults = Disciple_Tools_Groups_Post_Type::instance()->get_group_fields();
        }

        $field_options = $field["default"];
        $first = true;
        ?>
        <form method="post" name="field_edit_form">
        <input type="hidden" name="field_key" value="<?php echo esc_html( $field_key )?>">
        <input type="hidden" name="post_type" value="<?php echo esc_html( $post_type )?>">
        <input type="hidden" name="field-select" value="<?php echo esc_html( $post_type . "_" . $field_key )?>">
        <input type="hidden" name="field_select_nonce" id="field_select_nonce" value="<?php echo esc_attr( wp_create_nonce( 'field_select' ) ) ?>" />
        <input type="hidden" name="field_edit_nonce" id="field_edit_nonce" value="<?php echo esc_attr( wp_create_nonce( 'field_edit' ) ) ?>" />
            <?php if ( $field["type"] === "key_select" || $field["type"] === "multi_select" ){
                ?>

                <div id="add_option" style="">
                    <?php esc_html_e( "Add new option", 'disciple_tools' ) ?>
                    <br>
                    <input type="text" name="add_option" placeholder="label" />&nbsp;
                    <button type="submit" class="button"><?php echo esc_html( __( 'Add', 'disciple_tools' ) ) ?></button>
                </div>
                <br>
                <table class="widefat">
                    <thead>
                    <tr>
                        <td><?php esc_html_e( "Key", 'disciple_tools' ) ?></td>
                        <td><?php esc_html_e( "Label", 'disciple_tools' ) ?></td>
                        <td><?php esc_html_e( "Move", 'disciple_tools' ) ?></td>
                        <td><?php esc_html_e( "Delete", 'disciple_tools' ) ?></td>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ( $field_options as $key => $option ) :
                        if ( !( isset( $option["deleted"] ) && $option["deleted"] === true ) ):
                            $label = $option["label"] ?? ""; ?>
                            <tr>
                                <td>
                                    <?php echo esc_html( $key ) ?>
                                </td>
                                <td>
                                    <input name="field_key_<?php echo esc_html( $key )?>" type="text" value="<?php echo esc_html( $label ) ?>"/>
                                </td>
                                <td>
                                    <?php if ( !$first ) : ?>
                                        <button type="submit" name="move_up" value="<?php echo esc_html( $key ) ?>" class="button small" >↑</button>
                                        <button type="submit" name="move_down" value="<?php echo esc_html( $key ) ?>" class="button small" >↓</button>
                                    <?php endif; ?>
                                </td>
                                    <td>
                                        <?php if ( ( isset( $defaults[$field_key]["customizable"] ) && $defaults[$field_key]["customizable"] === "all" )
                                            || !isset( $defaults[$field_key]["default"][$key] )) : ?>
                                        <button type="submit" name="delete_option" value="<?php echo esc_html( $key ) ?>" class="button small" ><?php esc_html_e( "delete", 'disciple_tools' ) ?></button>
                                        <?php endif; ?>
                                    </td>
                            </tr>
                            <?php $first = false;
                        endif;
                    endforeach; ?>
    <!--                <tr style="padding-top:10px; border-top:solid black;"><td>Deleted options:</td></tr>-->
                    <?php foreach ( $field_options as $key => $option ) :
                        $label = $option["label"] ?? "";
                        if ( isset( $option["deleted"] ) && $option["deleted"] === true ): ?>
                            <tr style="background-color: #eee">
                                <td><?php echo esc_html( $key ) ?></td>
                                <td><?php echo esc_html( $label ) ?></td>
                                <td></td>
                                <td>
                                    <button type="submit" name="restore_option" value="<?php echo esc_html( $key ) ?>" class="button small" ><?php esc_html_e( "Restore", 'disciple_tools' ) ?></button>
                                </td>
                            </tr>
                        <?php endif;
                    endforeach; ?>
                    </tbody>
                </table>

                <br>
    <!--            <button type="button" onclick="jQuery('#add_seeker_path').toggle();" class="button">--><?php //esc_html_e( "Add", 'disciple_tools' ) ?><!--</button>-->
                <button type="submit" style="float:right;" class="button"><?php esc_html_e( "Save", 'disciple_tools' ) ?></button>


            <?php } else { ?>

                <table class="widefat">
                    <thead>
                    <tr>
                        <td><?php esc_html_e( "Label", 'disciple_tools' ) ?></td>
                        <td><?php esc_html_e( "Key", 'disciple_tools' ) ?></td>
                    </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <input name="field_key_<?php echo esc_html( $field_key )?>" type="text" value="<?php echo esc_html( $field["name"] ) ?>"/>
                            </td>
                            <td>
                                <?php echo esc_html( $field_key ) ?>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <br>
                <button type="submit" style="float:right;" class="button"><?php esc_html_e( "Save", 'disciple_tools' ) ?></button>
        <?php }

    }


    private function process_edit_field( $post_submission ){
        //save values
        $post_type = $post_submission["post_type"];
        $post_fields = $this->get_post_fields( $post_type );
        if ( isset( $post_fields[$post_submission["field_key"]]["default"] )){
            $field_customizations = dt_get_option( "dt_field_customizations" );
            $field_key = $post_submission["field_key"];
            if ( !isset( $field_customizations[$post_type][$field_key] ) ){
                $field_customizations[$post_type][$field_key] = [];
            }
            $custom_field = $field_customizations[$post_type][$field_key];
            $field = $post_fields[$field_key];
            $field_options = $field["default"];
            if ( $field["type"] === 'multi_select' || $field["type"] === "key_select" ){
                foreach ( $post_submission as $key => $val ){
                    if ( strpos( $key, "field_key_" ) === 0 ){
                        $option_key = substr( $key, 10 );
                        if ( $field_options[$option_key]["label"] != $val ){
                            $custom_field["default"][$option_key]["label"] = $val;
                        }
                        $field_options[$option_key]["label"] = $val;
                    }
                }
                //delete option
                if ( isset( $post_submission["delete_option"] ) ){
                    $custom_field["default"][$post_submission["delete_option"]]["deleted"] = true;
                    $field_options[ $post_submission["delete_option"] ]["deleted"] = true;
                }
                //delete option
                if ( isset( $post_submission["restore_option"] ) ){
                    $custom_field["default"][$post_submission["restore_option"]]["deleted"] = false;
                    $field_options[ $post_submission["restore_option"] ]["deleted"] = false;
                }
                //move option  up or down
                if ( isset( $post_submission["move_up"] ) || isset( $post_submission["move_down"] )){
                    $option_key = $post_submission["move_up"] ?? $post_submission["move_down"];
                    $direction = isset( $post_submission["move_up"] ) ? -1 : 1;
                    $pos = (int) array_search( $option_key, array_keys( $field_options ) ) + $direction;
                    $val = $field_options[ $option_key ];
                    unset( $field_options[ $option_key ] );
                    $field_options = array_merge(
                        array_slice( $field_options, 0, $pos ),
                        [ $option_key => $val ],
                        array_slice( $field_options, $pos )
                    );
                    $order = array_keys( $field_options );
                    $custom_field["order"] = $order;
                }
                /*
                 * add option
                 */
                if ( !empty( $post_submission["add_option"] ) ){
                    $option_key = $this->create_field_key( $post_submission["add_option"] );
                    if ( !isset( $field_options[$option_key] )){
                        if ( !empty( $option_key ) && !empty( $post_submission["add_option"] )){
                            $field_options[ $option_key ] = [ "label" => $post_submission["add_option"] ];
                            $custom_field["default"][$option_key] = [ "label" => $post_submission["add_option"] ];
                        }
                    } else {
                        self::admin_notice( __( "This option already exists", 'disciple_tools' ), "error" );
                    }
                }
            } elseif ( $field["type"] === 'date' || $field["type"] === 'text' ){
                if ( isset( $post_fields[$field_key] ) ){
                    //update name
                    if ( isset( $post_submission["field_key_" . $field_key] ) ){
                        $custom_field["name"] = $post_submission["field_key_" . $field_key];
                    }
                }
            }
            if ( !empty( $custom_field )){
                $field_customizations[$post_type][$field_key] = $custom_field;
            }
            update_option( "dt_field_customizations", $field_customizations );
        }
    }
    public function create_field_key( $s ){
        $string = str_replace( ' ', '_', $s ); // Replaces all spaces with hyphens.
        $ret = preg_replace( '/[^A-Za-z0-9\-_]/', '', $string ); // Removes special chars.
        return strtolower( $ret );
    }


    private function add_field(){

        ?>
        <form method="post">
            <input type="hidden" name="field_add_nonce" id="field_add_nonce" value="<?php echo esc_attr( wp_create_nonce( 'field_add' ) ) ?>" />

            <select name="post_type">
                <option value="contacts">Contacts</option>
                <option value="groups">Groups</option>
            </select>
            <label for="new_field_name"><?php esc_html_e( "New Field Name", 'disciple_tools' ) ?></label>
            <input name="new_field_name" id="new_field_name" required>
            <label><?php esc_html_e( "Field type", 'disciple_tools' ) ?></label>
            <select name="new_field_type" required>
                <option></option>
                <option value="key_select"><?php esc_html_e( "Dropdown", 'disciple_tools' ) ?></option>
                <option value="multi_select"><?php esc_html_e( "Multi Select", 'disciple_tools' ) ?></option>
                <option value="text"><?php esc_html_e( "Text", 'disciple_tools' ) ?></option>
                <option value="date"><?php esc_html_e( "Date", 'disciple_tools' ) ?></option>
            </select>
<!--            <label>Tile</label>-->
<!--            <select>-->
<!---->
<!--            </select>-->
            <button type="submit" class="button"><?php esc_html_e( "add", 'disciple_tools' ) ?></button>
        </form>
        <?php
    }

    private function process_add_field( $post_submission ){
        if ( isset( $post_submission["new_field_name"], $post_submission["new_field_type"], $post_submission["post_type"] ) ){
            $post_type = $post_submission["post_type"];
            $field_type = $post_submission["new_field_type"];
            $field_tile = $post_submission["new_field_tile"] ?? '';
            $field_key = $this->create_field_key( $post_submission["new_field_name"] );
            $post_fields = $this->get_post_fields( $post_type );
            if ( isset( $post_fields[ $field_key ] )){
                self::admin_notice( __( "Field already exists", 'disciple_tools' ), "error" );
                return false;
            }
            $new_field = [];
            if ( $field_type === "key_select" ){
                $new_field = [
                    'name' => $post_submission["new_field_name"],
                    'default' => [],
                    'type' => 'key_select',
                    'section' => $field_tile,
                    'customizable' => 'all'
                ];
            } elseif ( $field_type === "multi_select" ){
                $new_field = [
                    'name' => $post_submission["new_field_name"],
                    'default' => [],
                    'type' => 'multi_select',
                    'section' => $field_tile,
                    'customizable' => 'all'
                ];
            } elseif ( $field_type === "date" ){
                $new_field = [
                    'name'        => $post_submission["new_field_name"],
                    'type'        => 'date',
                    'default'     => '',
                    'section'     => $field_tile,
                    'customizable' => 'all'
                ];
            } elseif ( $field_type === "text" ){
                $new_field = [
                    'name'        => $post_submission["new_field_name"],
                    'type'        => 'text',
                    'default'     => '',
                    'section'     => $field_tile,
                    'customizable' => 'all'
                ];
            }

            $custom_field_options = dt_get_option( "dt_field_customizations" );
            $custom_field_options[$post_type][$field_key] = $new_field;
            update_option( "dt_field_customizations", $custom_field_options );
            self::admin_notice( __( "Field added successfully", 'disciple_tools' ), "success" );
            return $field_key;
        }
        return false;
    }

    /**
     * Display admin notice
     * @param $notice string
     * @param $type string error|success|warning
     */
    public static function admin_notice( string $notice, string $type ) {
        ?>
        <div class="notice notice-<?php echo esc_attr( $type ) ?> is-dismissible">
            <p><?php echo esc_html( $notice ) ?></p>
        </div>
        <?php
    }
}
Disciple_Tools_Tab_Custom_Fields::instance();


