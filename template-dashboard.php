<?php
/*
Template Name: Metrics
*/
dt_please_log_in();

if ( !current_user_can( 'access_contacts' ) ) {
    wp_safe_redirect( '/settings' );
    exit();
}


$url_path = dt_get_url_path();

get_header(); ?>

<div style="padding:15px" class="template-metrics">

    <div id="inner-content" class="grid-x grid-margin-x grid-margin-y">

        <div class="large-2 medium-3 small-12 cell" id="side-nav-container">

            <section id="metrics-side-section" class="medium-12 cell">

                <div class="bordered-box">

                    <div class="section-body">

                        <ul id="metrics-sidemenu" class="vertical menu accordion-menu" data-accordion-menu>

                            <?php
                            //default first dashboard menu item
                            add_filter( 'dt_dashboard_menu', function ( $menu ){
                                $menu[] = [
                                    "id" => "main",
                                    'link' => "",
                                    "icon" => get_template_directory_uri() . '/dt-assets/images/house.svg',
                                    "label" => __( "Dashboard", 'disciple_tools' ),
                                    "submenu" => [
                                        [ "id" => 'test', "label" => "Bob"]
                                    ]
                                ];
                                $menu[] = [
                                    "id" => "main2",
                                    'link' => "main2",
                                    "icon" => get_template_directory_uri() . '/dt-assets/images/house.svg',
                                    "label" => __( "Dashboard Page 2", 'disciple_tools' ),
                                ];
                                $menu[] = [
                                    "id" => "main3",
                                    'link' => "main3",
                                    "icon" => get_template_directory_uri() . '/dt-assets/images/house.svg',
                                    "label" => __( "Dashboard Page 3", 'disciple_tools' ),
                                ];
                                return $menu;
                            }, 5, 1 );

                            //get more menu items
                            $dashboard_menu = apply_filters( 'dt_dashboard_menu', [] );

                            //display menu items
                            foreach ( $dashboard_menu as $menu_item ): ?>
                                <li>
                                    <a href="<?php echo esc_url( home_url( '/dashboard/' . $menu_item["id"] ) ) ?>"><img class="dt-icon" src="<?php echo esc_html( $menu_item["icon"] ) ?>"/> <span><?php echo esc_html( $menu_item["label"] ); ?></span></a>
                                    <?php
                                    //show submenus
//                                    if ( isset( $menu_item['submenu'] ) && !empty( $menu_item['submenu'] ) ) : ?>
<!--                                        <ul class="menu vertical nested">-->
<!--                                            --><?php //foreach ( $menu_item['submenu'] as $dt_submenu_item ) : ?>
<!--                                                    <li><a href="--><?php //echo esc_url( $dt_submenu_item['id'] ) ?><!--">--><?php //echo esc_html( $dt_submenu_item['label'] ) ?><!--</a></li>-->
<!--                                            --><?php //endforeach; ?>
<!--                                        </ul>-->
<!--                                    --><?php //endif; ?>
                                </li>
                            <?php endforeach;

                            ?>

                        </ul>

                    </div>

                </div>

            </section>

        </div>

        <div class="large-10 medium-9 small-12 cell" style="margin:0">

            <section id="dashboard-container" class="medium-12 cell dashboard-page">

                <?php
                $rows = apply_filters( 'dt_dashboard_rows', [], $url_path );
                foreach ( $rows as $row ):
                    if ( !isset( $row["id"] ) ) {
                        continue;
                    } ?>
                    <div class="dash-cards">
                        <?php do_action( "dt_dashboard_tiles", $url_path, $row["id"] ); ?>
                    </div>
                <?php endforeach ?>

            </section>

        </div>

    </div> <!-- end #inner-content -->

</div> <!-- end #content -->

<?php get_footer(); ?>
