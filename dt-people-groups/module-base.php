<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

/**
 * Class DT_People_Groups_Base
 * Load the core post type hooks into the Disciple Tools system
 */
class DT_People_Groups_Base extends DT_Module_Base {

    public $post_type = "peoplegroups";
    public $module = "peoplegroups_base";
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

        add_action( 'after_setup_theme', [ $this, 'after_setup_theme' ], 100 );
        add_action( 'p2p_init', [ $this, 'p2p_init' ] );
        add_filter( 'dt_set_roles_and_permissions', [ $this, 'dt_set_roles_and_permissions' ], 20, 1 );
        add_filter( 'dt_custom_fields_settings', [ $this, 'dt_custom_fields_settings' ], 10, 2 );

        if ( ! dt_is_module_enabled( 'peoplegroups_ui' ) ) {
            add_filter( 'desktop_navbar_menu_options', [ $this, 'add_navigation_links' ], 25, 1 );
            add_filter( 'dt_nav_add_post_menu', [ $this, 'dt_nav_add_post_menu' ], 25, 1 );
        }
    }

    public function after_setup_theme(){
        if ( class_exists( 'Disciple_Tools_Post_Type_Template' )) {
            new Disciple_Tools_Post_Type_Template( $this->post_type, $this->single_name, $this->plural_name );
        }
    }
    public function dt_custom_fields_settings( $fields, $post_type ){
        if ( $post_type === $this->post_type ){

            $fields['rop3'] = [
                'name'        => __( 'ROP3', 'disciple_tools' ),
                'description' => '',
                'type'        => 'text',
                'default'     => '',
                'tile' => 'details',
            ];
            $fields['country'] = [
                'name'        => __( 'Country', 'disciple_tools' ),
                'description' => '',
                'type'        => 'key_select',
                'default'     => $this->countries(),
                'tile' => 'details',
            ];
            $fields['pg_unique_key'] = [
                'name'        => __( 'Unique Key', 'disciple_tools' ),
                'description' => '',
                'type'        => 'text',
                'default'     => '',
                'tile' => '',
                'hidden' => true,
            ];

            $fields["contacts"] = [
                "name" => __( 'Contacts', 'disciple_tools' ),
                'description' => _x( 'The people groups represented by this contact.', 'Optional Documentation', 'disciple_tools' ),
                "type" => "connection",
                "post_type" => "contacts",
                "p2p_direction" => "to",
                "p2p_key" => "contacts_to_peoplegroups",
                'tile'     => 'other',
                'icon' => get_template_directory_uri() . "/dt-assets/images/people-group.svg",
            ];
            $fields["groups"] = [
                "name" => __( 'Groups', 'disciple_tools' ),
                'description' => _x( 'The people groups represented by this group.', 'Optional Documentation', 'disciple_tools' ),
                "type" => "connection",
                "post_type" => "groups",
                "p2p_direction" => "to",
                "p2p_key" => "groups_to_peoplegroups",
                'tile'     => 'other',
                'icon' => get_template_directory_uri() . "/dt-assets/images/people-group.svg",
            ];
        }

        return $fields;
    }

    public function p2p_init(){}

    public function dt_set_roles_and_permissions( $expected_roles ){

        $expected_roles["peoplegroups_admin"] = [
            "label" => __( 'People Groups Admin', 'disciple-tools-training' ),
            "description" => __( 'Admin access to all people', 'disciple-tools-training' ),
            "permissions" => []
        ];
        if ( !isset( $expected_roles["multiplier"] ) ){
            $expected_roles["multiplier"] = [
                "label" => __( 'Multiplier', 'disciple_tools' ),
                "permissions" => []
            ];
        }

        // if the user can access contact they also can access this post type
        foreach ( $expected_roles as $role => $role_value ){
            if ( isset( $expected_roles[$role]["permissions"]['access_contacts'] ) && $expected_roles[$role]["permissions"]['access_contacts'] ){
                $expected_roles[$role]["permissions"]['access_' . $this->post_type ] = true;
//                $expected_roles[$role]["permissions"]['create_' . $this->post_type] = true;
                $expected_roles[$role]["permissions"]['view_any_' . $this->post_type] = true;
            }
        }

        if ( isset( $expected_roles["administrator"] ) ){
            $expected_roles["administrator"]["permissions"]['view_any_'.$this->post_type ] = true;
            $expected_roles["administrator"]["permissions"][ 'dt_all_admin_' . $this->post_type] = true;
            $expected_roles["administrator"]["permissions"]['update_any_'.$this->post_type ] = true;
            $expected_roles["administrator"]["permissions"]['list_'.$this->post_type ] = true;
        }
        if ( isset( $expected_roles["dt_admin"] ) ){
            $expected_roles["dt_admin"]["permissions"]['view_any_'.$this->post_type ] = true;
            $expected_roles["dt_admin"]["permissions"]['update_any_'.$this->post_type ] = true;
            $expected_roles["dt_admin"]["permissions"][ 'dt_all_admin_' . $this->post_type] = true;
            $expected_roles["dt_admin"]["permissions"]['list_'.$this->post_type ] = true;
        }
        if ( isset( $expected_roles["peoplegroups_admin"] ) ){
            $expected_roles["peoplegroups_admin"]["permissions"]['view_any_'.$this->post_type ] = true;
            $expected_roles["peoplegroups_admin"]["permissions"]['update_any_'.$this->post_type ] = true;
            $expected_roles["peoplegroups_admin"]["permissions"]['dt_all_admin_' . $this->post_type] = true;
            $expected_roles["peoplegroups_admin"]["permissions"]['list_'.$this->post_type ] = true;
            $expected_roles["peoplegroups_admin"]["permissions"]['create_' . $this->post_type] = true;
        }

        return $expected_roles;
    }

    public function countries(){
        return [
            "AF" =>"Afghanistan",
            "AL" =>"Albania",
            "AG" =>"Algeria",
            "AQ" =>"American Samoa",
            "AN" =>"Andorra",
            "AO" =>"Angola",
            "AV" =>"Anguilla",
            "AC" =>"Antigua and Barbuda",
            "AR" =>"Argentina",
            "AM" =>"Armenia",
            "AA" =>"Aruba",
            "AS" =>"Australia",
            "AU" =>"Austria",
            "AJ" =>"Azerbaijan",
            "BF" =>"Bahamas",
            "BA" =>"Bahrain",
            "BG" =>"Bangladesh",
            "BB" =>"Barbados",
            "BO" =>"Belarus",
            "BE" =>"Belgium",
            "BH" =>"Belize",
            "BN" =>"Benin",
            "BD" =>"Bermuda",
            "BT" =>"Bhutan",
            "BL" =>"Bolivia",
            "BK" =>"Bosnia-Herzegovina",
            "BC" =>"Botswana",
            "BR" =>"Brazil",
            "IO" =>"British Indian Ocean Territory",
            "VI" =>"British Virgin Islands",
            "BX" =>"Brunei",
            "BU" =>"Bulgaria",
            "UV" =>"Burkina Faso",
            "BY" =>"Burundi",
            "CB" =>"Cambodia",
            "CM" =>"Cameroon",
            "CA" =>"Canada",
            "CV" =>"Cape Verde",
            "CJ" =>"Cayman Islands",
            "CT" =>"Central African Republic",
            "CD" =>"Chad",
            "CI" =>"Chile",
            "CH" =>"China",
            "HK" =>"China,Hong Kong",
            "MC" =>"China, Macau",
            "KT" =>"Christmas Island",
            "CK" =>"Cocos (Keeling) Islands",
            "CO" =>"Colombia",
            "CN" =>"Comoros",
            "CG" =>"Congo, Democratic Republic of",
            "CF" =>"Congo, Republic of the",
            "CW" =>"Cook Islands",
            "CS" =>"Costa Rica",
            "IV" =>"Côte d'Ivoire",
            "HR" =>"Croatia",
            "CU" =>"Cuba",
            "UC" =>"Curacao",
            "CY" =>"Cyprus",
            "EZ" =>"Czechia",
            "DA" =>"Denmark",
            "DJ" =>"Djibouti",
            "DO" =>"Dominica",
            "DR" =>"Dominican Republic",
            "TT" =>"East Timor",
            "EC" =>"Ecuador",
            "EG" =>"Egypt",
            "ES" =>"El Salvador",
            "EK" =>"Equatorial Guinea",
            "ER" =>"Eritrea",
            "EN" =>"Estonia",
            "WZ" =>"Eswatini",
            "ET" =>"Ethiopia",
            "FK" =>"Falkland Islands",
            "FO" =>"Faroe Islands",
            "FJ" =>"Fiji",
            "FI" =>"Finland",
            "FR" =>"France",
            "FG" =>"French Guiana",
            "FP" =>"French Polynesia",
            "GB" =>"Gabon",
            "GA" =>"Gambia",
            "GG" =>"Georgia",
            "GM" =>"Germany",
            "GH" =>"Ghana",
            "GI" =>"Gibraltar",
            "GR" =>"Greece",
            "GL" =>"Greenland",
            "GJ" =>"Grenada",
            "GP" =>"Guadeloupe",
            "GQ" =>"Guam",
            "GT" =>"Guatemala",
            "GV" =>"Guinea",
            "PU" =>"Guinea-Bissau",
            "GY" =>"Guyana",
            "HA" =>"Haiti",
            "HO" =>"Honduras",
            "HU" =>"Hungary",
            "IC" =>"Iceland",
            "IN" =>"India",
            "ID" =>"Indonesia",
            "IR" =>"Iran",
            "IZ" =>"Iraq",
            "EI" =>"Ireland",
            "IM" =>"Isle of Man",
            "IS" =>"Israel",
            "IT" =>"Italy",
            "JM" =>"Jamaica",
            "JA" =>"Japan",
            "JO" =>"Jordan",
            "KZ" =>"Kazakhstan",
            "KE" =>"Kenya",
            "KR" =>"Kiribati (Gilbert)",
            "KN" =>"Korea, North",
            "KS" =>"Korea, South",
            "KV" =>"Kosovo",
            "KU" =>"Kuwait",
            "KG" =>"Kyrgyzstan",
            "LA" =>"Laos",
            "LG" =>"Latvia",
            "LE" =>"Lebanon",
            "LT" =>"Lesotho",
            "LI" =>"Liberia",
            "LY" =>"Libya",
            "LS" =>"Liechtenstein",
            "LH" =>"Lithuania",
            "LU" =>"Luxembourg",
            "MA" =>"Madagascar",
            "MI" =>"Malawi",
            "MY" =>"Malaysia",
            "MV" =>"Maldives",
            "ML" =>"Mali",
            "MT" =>"Malta",
            "RM" =>"Marshall Islands",
            "MB" =>"Martinique",
            "MR" =>"Mauritania",
            "MP" =>"Mauritius",
            "MF" =>"Mayotte",
            "MX" =>"Mexico",
            "FM" =>"Micronesia, Federated States",
            "MD" =>"Moldova",
            "MN" =>"Monaco",
            "MG" =>"Mongolia",
            "MJ" =>"Montenegro",
            "MH" =>"Montserrat",
            "MO" =>"Morocco",
            "MZ" =>"Mozambique",
            "BM" =>"Myanmar (Burma)",
            "WA" =>"Namibia",
            "NR" =>"Nauru",
            "NP" =>"Nepal",
            "NL" =>"Netherlands",
            "NC" =>"New Caledonia",
            "NZ" =>"New Zealand",
            "NU" =>"Nicaragua",
            "NG" =>"Niger",
            "NI" =>"Nigeria",
            "NE" =>"Niue",
            "NF" =>"Norfolk Island",
            "MK" =>"North Macedonia",
            "CQ" =>"Northern Mariana Islands",
            "NO" =>"Norway",
            "MU" =>"Oman",
            "PK" =>"Pakistan",
            "PS" =>"Palau",
            "PM" =>"Panama",
            "PP" =>"Papua New Guinea",
            "PA" =>"Paraguay",
            "PE" =>"Peru",
            "RP" =>"Philippines",
            "PC" =>"Pitcairn Islands",
            "PL" =>"Poland",
            "PO" =>"Portugal",
            "RQ" =>"Puerto Rico",
            "QA" =>"Qatar",
            "RE" =>"Reunion",
            "RO" =>"Romania",
            "RS" =>"Russia",
            "RW" =>"Rwanda",
            "SH" =>"Saint Helena",
            "SC" =>"Saint Kitts and Nevis",
            "ST" =>"Saint Lucia",
            "SB" =>"Saint Pierre and Miquelon",
            "WS" =>"Samoa",
            "SM" =>"San Marino",
            "TP" =>"São Tomé and Príncipe",
            "SA" =>"Saudi Arabia",
            "SG" =>"Senegal",
            "RI" =>"Serbia",
            "SE" =>"Seychelles",
            "SL" =>"Sierra Leone",
            "SN" =>"Singapore",
            "NN" =>"Sint Maarten",
            "LO" =>"Slovakia",
            "SI" =>"Slovenia",
            "BP" =>"Solomon Islands",
            "SO" =>"Somalia",
            "SF" =>"South Africa",
            "OD" =>"South Sudan",
            "SP" =>"Spain",
            "CE" =>"Sri Lanka",
            "VC" =>"St Vincent and Grenadines",
            "SU" =>"Sudan",
            "NS" =>"Suriname",
            "SV" =>"Svalbard",
            "SW" =>"Sweden",
            "SZ" =>"Switzerland",
            "SY" =>"Syria",
            "TW" =>"Taiwan",
            "TI" =>"Tajikistan",
            "TZ" =>"Tanzania",
            "TH" =>"Thailand",
            "TO" =>"Togo",
            "TL" =>"Tokelau",
            "TN" =>"Tonga",
            "TD" =>"Trinidad and Tobago",
            "TS" =>"Tunisia",
            "TU" =>"Turkey",
            "TX" =>"Turkmenistan",
            "TK" =>"Turks and Caicos Islands",
            "TV" =>"Tuvalu",
            "UG" =>"Uganda",
            "UP" =>"Ukraine",
            "AE" =>"United Arab Emirates",
            "UK" =>"United Kingdom",
            "US" =>"United States",
            "UY" =>"Uruguay",
            "UZ" =>"Uzbekistan",
            "NH" =>"Vanuatu",
            "VT" =>"Vatican City",
            "VE" =>"Venezuela",
            "VM" =>"Vietnam",
            "VQ" =>"Virgin Islands (U.S.)",
            "WF" =>"Wallis and Futuna Islands",
            "WE" =>"West Bank / Gaza",
            "WI" =>"Western Sahara",
            "YM" =>"Yemen",
            "ZA" =>"Zambia",
            "ZI" =>"Zimbabwe"
        ];
    }

    public function add_navigation_links( $tabs ) {
        if ( isset( $tabs[$this->post_type] ) ) {
            unset( $tabs[$this->post_type] );
        }
        return $tabs;
    }

    public function dt_nav_add_post_menu( $links ){
        if ( isset( $links[$this->post_type] ) ) {
            unset( $links[$this->post_type] );
        }
        return $links;
    }

}


