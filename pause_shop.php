<?php
/*
Plugin Name: Pause shop
Description: Disable add-to-cart and checkout, disabling creating new orders, and show a notice. For Woocommerce.
Author: y3ro
Domain Path: /languages
Text Domain: pause-shop
Version: 0.8.6
License: MIT
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

load_plugin_textdomain( 'pause-shop', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

function pause_shop_add_to_cart_disabled_msg() {
    $loc_msg = __( 'The purchase function has been disabled while we are performing maintenance on the site. We will be back shortly.', 
                   'pause-shop' );

    ?>
	<div class="pause-msg-at-product">
        <?php echo esc_html($loc_msg); ?>
    </div>
    <?php   
}

function pause_shop_filter_order_button_html() {
    $loc_msg = __( 'The purchase function has been disabled while we are performing maintenance on the site. We will be back shortly.', 
                   'pause-shop' );
    $html = sprintf('<div class="pause-msg-at-order">%s</div>', $loc_msg);

    return $html;
}

function pause_shop_block_order() {
    $loc_msg = __( 'The purchase function has been disabled while we are performing maintenance on the site. We will be back shortly.', 
                   'pause-shop' );
    wp_die($loc_msg);  // TODO: what is the right format?
}

function pause_shop_is_pause_day() {
    $periodicity = get_option('pause_shop_periodicity') ?: 'daily';
    $begin_date = get_option('pause_shop_begin_date') ?: '2000-01-01';
    $today = date('Y-m-d');

    switch ($periodicity) {
        case 'daily':
            return true;
        case 'weekly':
            $weekday_number_period = date('N', strtotime($begin_date));
            $weekday_number_today = date('N', strtotime($today));
            return $weekday_number_period == $weekday_number_today;
        case 'monthly':
            $day_number_period = date('d', strtotime($begin_date));
            $day_number_today = date('d', strtotime($today));
            return $day_number_period == $day_number_today;
        default:
            return false;
    }
}

function pause_shop_is_scheduled_paused() {
    $timezone = new DateTimeZone(get_option('pause_shop_timezone') ?: 'UTC');
    $scheduled_pause_enabled = get_option('pause_shop_scheduled_pause_enabled') ?: false;

    $begin_time = get_option('pause_shop_begin_time');
    $end_time = get_option('pause_shop_end_time');
    $date = new DateTime('now', $timezone);
    $time = $date->format('H:i:s');

    $is_scheduled_paused = $scheduled_pause_enabled &&
        pause_shop_is_pause_day() &&    
        $time <= $end_time && $time >= $begin_time;

    return $is_scheduled_paused;
}

function pause_shop_pause_shop() {
    $on_demand_paused = get_option('pause_shop_on_demand_paused') ?: false;
    $schedule_paused = pause_shop_is_scheduled_paused();

    if ($on_demand_paused || $schedule_paused) {
		add_filter('woocommerce_is_purchasable', '__return_false');
		add_action('woocommerce_single_product_summary', 'pause_shop_add_to_cart_disabled_msg');
		add_filter('woocommerce_order_button_html', 'pause_shop_filter_order_button_html', 10, 2);
        add_action('woocommerce_before_checkout_process', 'pause_shop_block_order');
    }
}

add_action('wp', 'pause_shop_pause_shop');

/* Admin menu entry */

function pause_shop_pause_shop_menu() {
    add_menu_page(
        'Pause shop Settings',
        'Pause shop',
        'manage_options',
        'pause-shop-settings-group',
        'pause_shop_settings_page',
        'dashicons-controls-pause',
    );
}
add_action('admin_menu', 'pause_shop_pause_shop_menu');

/* Admin settings page */

function pause_shop_echo_pause_unpause_button() {
    $pause = get_option('pause_shop_on_demand_paused') ?: false;
    $scheduled_pause_enabled = get_option('pause_shop_scheduled_pause_enabled') ?: false;
    $schedule_paused = pause_shop_is_scheduled_paused();
    $timezone = get_option('pause_shop_timezone') ?: 'UTC';
    $begin_time = get_option('pause_shop_begin_time');
    $end_time = get_option('pause_shop_end_time');
    $periodicity = get_option('pause_shop_periodicity') ?: 'daily';
    $begin_date = get_option('pause_shop_begin_date');
    $pause_on_demand_title = __('Pause on demand', 'pause-shop');
    $pause_state_title = __('State', 'pause-shop');
    $pause_state = $pause ? __('Paused', 'pause-shop') : __('Unpaused', 'pause-shop');
    $pause_text = __('Pause shop', 'pause-shop');
    $unpause_text = __('Unpause shop', 'pause-shop');
    $button_text = $pause ? $unpause_text : $pause_text;
    ?>
    <h3><?php echo esc_html($pause_on_demand_title); ?></h3>
    <p>
        <?php echo esc_html($pause_state_title); ?>: <?php echo esc_html($pause_state); ?>
    </p>
    <form method="post" action="options.php" class="pause-on-demand-form">
        <?php settings_fields('pause-shop-settings-group'); ?>
        <?php do_settings_sections('pause-shop-settings-group'); ?>
        <input type="hidden" name="pause_shop_on_demand_paused" class="button button-primary" 
            value="<?php echo esc_attr(!$pause); ?>">
        <input type="hidden" name="pause_shop_scheduled_pause_enabled" 
        value="<?php echo esc_attr($scheduled_pause_enabled); ?>">
        <input type="hidden" name="pause_shop_schedule_paused" value="<?php echo esc_attr($schedule_paused); ?>">
        <input type="hidden" name="pause_shop_timezone" value="<?php echo esc_attr($timezone); ?>">
        <input type="hidden" name="pause_shop_begin_time" value="<?php echo esc_attr($begin_time); ?>">
        <input type="hidden" name="pause_shop_end_time" value="<?php echo esc_attr($end_time); ?>">
        <input type="hidden" name="pause_shop_periodicity" value="<?php echo esc_attr($periodicity); ?>">
        <input type="hidden" name="pause_shop_begin_date" value="<?php echo esc_attr($begin_date); ?>">
            <?php submit_button(
                $button_text, 'primary', 'submit', true, array()); ?>
    </form>
    <?php
}

function pause_shop_echo_scheduled_pause_controls() {
    $scheduled_pause_enabled_title = __('Enable scheduled pause', 'pause-shop');
    $pause = pause_shop_is_scheduled_paused();
    $on_demand_paused = get_option('pause_shop_on_demand_paused') ?: false;
    $pause_state_title = __('State', 'pause-shop');
    $pause_state = $pause ? __('Paused', 'pause-shop') : __('Unpaused', 'pause-shop');
    $scheduled_pause_enabled = get_option('pause_shop_scheduled_pause_enabled') ?: false;
    $scheduled_pause_enabled_checked_str = $scheduled_pause_enabled ? 'checked' : '';
    $timezone_title = __('Timezone', 'pause-shop');
    $begin_time_title = __('Begin time', 'pause-shop');
    $end_time_title = __('End time', 'pause-shop');
    $periodicity_title = __('Periodicity', 'pause-shop');
    $begin_date_title = __('Begin date', 'pause-shop');
    $current_periodicity = get_option('pause_shop_periodicity');

    ?>
    <h3><?php echo esc_html__('Scheduled pause', 'pause-shop'); ?></h3>
        <p>
            <?php echo esc_html($pause_state_title); ?>: <?php echo esc_html($pause_state); ?>
        </p>
        <form method="post" action="options.php">
            <?php settings_fields('pause-shop-settings-group'); ?>
            <?php do_settings_sections('pause-shop-settings-group'); ?>
            <input type="hidden" name="pause_shop_on_demand_paused" value="<?php echo esc_attr($on_demand_paused); ?>">
            <table class="form-table">
                <tr valign="top">
                    <input id="scheduled-pause-enabled" type="checkbox" name="pause_shop_scheduled_pause_enabled" 
                    <?php echo esc_attr($scheduled_pause_enabled_checked_str); ?>>
                    <label for="scheduled_pause_enabled"><?php echo esc_html($scheduled_pause_enabled_title); ?></label>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php echo esc_html($timezone_title); ?></th>
                    <td>
                        <select name="pause_shop_timezone" class="scheduled-pause-input">
                        <?php
                            $timezones = DateTimeZone::listIdentifiers(); # TODO: not localized
                            foreach($timezones as $timezone) {
                                $selected_str = $timezone == get_option('pause_shop_timezone') ? 'selected' : '';
                                ?>
                                <option value="<?php echo esc_attr($timezone); ?>" <?php echo esc_attr($selected_str); ?>>
                                    <?php echo esc_html($timezone); ?>
                                </option>
                                <?php
                            }
                        ?>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php echo esc_html($begin_time_title); ?></th>
                    <td>
                        <input type="time" name="pause_shop_begin_time" class="scheduled-pause-input"
                        value="<?php echo esc_attr(get_option('pause_shop_begin_time')); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php echo esc_html($end_time_title); ?></th>
                    <td>
                        <input type="time" name="pause_shop_end_time" class="scheduled-pause-input"
                        value="<?php echo esc_attr(get_option('pause_shop_end_time')); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php echo esc_html($periodicity_title); ?></th>
                    <td>
                        <select name="pause_shop_periodicity" class="scheduled-pause-input">
                            <option value="daily"
                                    <?php echo esc_attr('daily' == $current_periodicity ? 'selected' : ''); ?>>
                                <?php
                                    echo esc_html__('daily', 'pause-shop');
                                ?>
                            </option>
                            <option value="weekly"
                                    <?php echo esc_attr('weekly' == $current_periodicity ? 'selected' : ''); ?>>
                                <?php
                                    echo esc_html__('weekly', 'pause-shop');
                                ?>
                            </option>
                            <option value="monthly"
                                    <?php echo esc_attr('monthly' == $current_periodicity ? 'selected' : ''); ?>>
                                <?php
                                    echo esc_html__('monthly', 'pause-shop');
                                ?>
                            </option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php echo esc_html($begin_date_title); ?></th>
                    <td>
                        <input type="date" name="pause_shop_begin_date" class="scheduled-pause-input"
                        value="<?php echo esc_attr(get_option('pause_shop_begin_date')); ?>" />
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <?php
}

function pause_shop_get_all_endpoints_info() {
    $endpoints = array(
        "POST /wp-json/pause-shop/v0/pause_shop" => 
            __('Disable the add-to-cart and checkout buttons, and show a notice.', 'pause-shop'),
        "POST /wp-json/pause-shop/v0/unpause_shop" =>
            __('Enable the add-to-cart and checkout buttons, and hide the notice.', 'pause-shop'),
        "GET /wp-json/pause-shop/v0/is_on_demand_paused" =>
            __('Return the current on-demand pause status.', 'pause-shop'),
        "GET /wp-json/pause-shop/v0/is_scheduled_paused" =>
            __('Return the current scheduled pause status.', 'pause-shop'),
        "POST /wp-json/pause-shop/v0/set_timezone -F \"timezone=Europe/London\"" =>
            '<div>' . __('Set timezone for the scheduled pause.', 'pause-shop') . '</div>',
        "GET /wp-json/pause-shop/v0/get_timezone" =>
            __('Get timezone for the scheduled pause.', 'pause-shop'),
        "POST /wp-json/pause-shop/v0/set_begin_time -F \"begin_time=01:00\"" =>
            '<div>' . __('Set begin time for the scheduled pause.', 'pause-shop') . '</div>',
        "GET /wp-json/pause-shop/v0/get_begin_time" =>
            __('Get begin time for the scheduled pause.', 'pause-shop'),
        "POST /wp-json/pause-shop/v0/set_end_time -F \"end_time=01:30\"" =>
            '<div>' . __('Set end time for the scheduled pause.', 'pause-shop') . '</div>',
        "GET /wp-json/pause-shop/v0/get_end_time" =>
            __('Get end time for the scheduled pause.', 'pause-shop'),
        "POST /wp-json/pause-shop/v0/set_periodicity -F \"periodicity=monthly\"" => 
            '<div>' . __('Set periodicity for the scheduled pause.', 'pause-shop') . '</div>',
        "GET /wp-json/pause-shop/v0/get_periodicity" =>
            __('Get periodicity for the scheduled pause.', 'pause-shop'),
        "POST /wp-json/pause-shop/v0/set_begin_date -F \"begin_date=2020-01-01\"" => 
            '<div>' . __('Set begin date for the scheduled pause.', 'pause-shop') . '</div>',
        "GET /wp-json/pause-shop/v0/get_begin_date" =>
            __('Get begin date for the scheduled pause.', 'pause-shop'),
        "POST /wp-json/pause-shop/v0/enable_scheduled_pause" => 
            __('Enable the scheduled pause.', 'pause-shop'),
        "POST /wp-json/pause-shop/v0/disable_scheduled_pause" =>
            __('Disable the scheduled pause.', 'pause-shop'),
        "GET /wp-json/pause-shop/v0/is_scheduled_pause_enabled" =>
            __('Return the current scheduled pause status.', 'pause-shop'),
    );

    return $endpoints;
}

// Include the WP_List_Table class
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

// Define our custom WP_List_Table subclass
class Pause_Shop_REST_Endpoints_Table extends WP_List_Table {

    // Define the columns that we want to display in the table
    function get_columns() {
        $columns = array(
            'endpoint' => __('Endpoint', 'pause-shop'),
            'description' => __('Description', 'pause-shop'),
        );
        return $columns;
    }

    // Define the data that will be displayed in each column for each row of the table
    function prepare_items() {
        $data = array();
        $endpoints = pause_shop_get_all_endpoints_info();

        foreach ($endpoints as $endpoint => $description) {
            $row = array(
                'endpoint' => $endpoint,
                'description' => $description,
            );

            $data[] = $row;
        }

        $this->_column_headers = array( $this->get_columns(), array(), array(), 'endpoint' );
        $this->items = $data;
    }

    // Define what is displayed in each column
    function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'endpoint':
            case 'description':
                return $item[ $column_name ];
            default:
                return print_r( $item, true );
        }
    }
}

function pause_shop_echo_help_text() {
    $help_title = __('Available REST endpoints', 'pause-shop');

    $wp_app_passwds_doc_link = "https://make.wordpress.org/core/2020/11/05/application-passwords-integration-guide/";   
    $source_code_link = "https://github.com/y3ro/pause_shop";
    $source_code_link_text = __("Source code", "pause-shop");
    
    ?>
    <h3>
        <?php echo esc_html($help_title); ?>
    </h3>
    <?php
        // Create a new instance of our custom WP_List_Table subclass
        $table = new Pause_Shop_REST_Endpoints_Table();

        // Output the table on the admin page
        $table->prepare_items();
        $table->display();
    ?>
    <p>
        <?php echo esc_html__("You can use application passwords to authenticate. See the WordPress documentation for more information.", 'pause-shop'); ?>
        <a href="<?php echo esc_url($wp_app_passwds_doc_link) ?>" target="_blank">
            <?php echo esc_html__('Wordpress documentation', 'pause-shop') ?>
        </a>
    </p>
    <div class="pause-shop-source-link">
        <a class="pause-shop-source-button" 
         href="<?php echo esc_url($source_code_link); ?>" target="_blank">
            <span class="github-icon">
                <svg height="22" viewBox="0 0 16 16" width="32" aria-hidden="true">
                    <path fill="#fff" d="M8 0a8 8 0 0 0-8 8 8 8 0 0 0 5.312 7.594c.38.07.52-.164.52-.367 0-.18-.007-.82-.012-1.605-2.12.454-2.563-.508-2.563-.508-.345-.88-.842-1.114-.842-1.114-.687-.47.052-.46.052-.46.76.053 1.16.783 1.16.783.677 1.16 1.777.823 2.213.63.068-.494.266-.823.485-1.012-1.7-.195-3.488-.85-3.488-3.787 0-.837.3-1.524.793-2.056-.08-.196-.343-.976.076-2.03 0 0 .645-.207 2.107.785A7.36 7.36 0 0 1 8 4.354c.64.004 1.286.086 1.887.255 1.462-.992 2.107-.785 2.107-.785.42 1.054.158 1.834.08 2.03.495.532.792 1.218.792 2.055 0 2.944-1.79 3.59-3.497 3.78.274.237.518.704.518 1.416 0 1.022-.01 1.845-.01 2.096 0 .203.137.438.524.365A8.008 8.008 0 0 0 16 8a8 8 0 0 0-8-8z"/>
                </svg>
            </span>
            <span>
                <?php echo esc_html($source_code_link_text);?>
            </span>
        </a>
    </div>
    <?php    
}

function pause_shop_echo_donations_text() {
    $show_donations = true;  # this can only be changed here
    $donations_title = __('Donations', 'pause-shop');
    $ko_fi_link = 'https://ko-fi.com/y3ro752694';
    $ko_fi_msg = __('If you like this plugin and want me to keep working on it, please consider buying me a coffee :)', 'pause-shop');
    $ko_fi_btn_image_alt = __('Buy Me a Coffee at ko-fi.com', 'pause-shop');
    
    if ($show_donations): ?>
        <div class="pause-shop-odd-section">
            <h3>
                <?php echo esc_html($donations_title) ?>
            </h3>
            <p>
                <?php echo esc_html($ko_fi_msg); ?>
            </p>
            <a href="<?php echo esc_url($ko_fi_link); ?>" target="_blank">
                <img class="pause-shop-donations-button" 
                src="https://cdn.ko-fi.com/cdn/kofi1.png?v=2" 
                alt="<?php echo esc_attr($ko_fi_btn_image_alt); ?>" />
            </a>
        </div>
    <?php endif;
}

function pause_shop_settings_page() {
    $settings_page_title = __('Pause shop Settings', 'pause-shop');
    ?>
    <div id="pause-shop-settings">
        <div class="wrap">
            <h2><?php echo esc_html($settings_page_title); ?></h2>
            <div class="pause-shop-odd-section">
                <?php pause_shop_echo_pause_unpause_button(); ?>
            </div>
            <div>
                <?php pause_shop_echo_scheduled_pause_controls(); ?>
            </div>
        </div>
        <?php pause_shop_echo_donations_text(); ?>
        <div class="pause-shop-help">
            <?php pause_shop_echo_help_text(); ?>
        </div>
    </div>
    <?php
}

/* Admin settings */

function pause_shop_register_settings() {
    register_setting('pause-shop-settings-group', 'pause_shop_timezone');
    register_setting('pause-shop-settings-group', 'pause_shop_begin_time');
    register_setting('pause-shop-settings-group', 'pause_shop_end_time');
    register_setting('pause-shop-settings-group', 'pause_shop_on_demand_paused');
    register_setting('pause-shop-settings-group', 'pause_shop_schedule_paused');
    register_setting('pause-shop-settings-group', 'pause_shop_scheduled_pause_enabled');
    register_setting('pause-shop-settings-group', 'pause_shop_begin_date');
    register_setting('pause-shop-settings-group', 'pause_shop_periodicity');
}
add_action('admin_init', 'pause_shop_register_settings');

/* REST endpoints */

function pause_shop_activate_on_demand_pause() {
    update_option( 'pause_shop_on_demand_paused', true );
    return array( 'success' => true );
}

function pause_shop_deactivate_on_demand_pause() {
    update_option( 'pause_shop_on_demand_paused', false );
    return array( 'success' => true );
}

function pause_shop_set_timezone() {
    $timezone = sanitize_text_field($_POST['timezone']);
    $timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);

    if (!in_array($timezone, $timezones)) {
        return array( 
            'success' => false, 'error' => 'Invalid timezone' );
    }

    update_option( 'pause_shop_timezone', $timezone );
    return array( 'success' => true );
}

function pause_shop_is_valid_time($time_str) {
    return preg_match('/^([01][0-9]|2[0-3]):([0-5][0-9])$/', $time_str);
}

function pause_shop_set_begin_time() {
    $begin_time = sanitize_text_field($_POST['begin_time']);

    if (!pause_shop_is_valid_time($begin_time)) {
        return array( 
            'success' => false, 'error' => 'Invalid begin time' );
    }

    update_option( 'pause_shop_begin_time', $begin_time );
    return array( 'success' => true );
}

function pause_shop_set_end_time() {
    $end_time = sanitize_text_field($_POST['end_time']);

    if (!pause_shop_is_valid_time($end_time)) {
        return array( 
            'success' => false, 'error' => 'Invalid end time' );
    }

    update_option( 'pause_shop_end_time', $end_time );
    return array( 'success' => true );
}

function pause_shop_enable_scheduled_pause() {
    update_option( 'pause_shop_scheduled_pause_enabled', true );
    return array( 'success' => true );
}

function pause_shop_disable_scheduled_pause() {
    update_option( 'pause_shop_scheduled_pause_enabled', false );
    return array( 'success' => true );
}

function pause_shop_set_periodicity() {
    $periodicity = sanitize_text_field($_POST['periodicity']);
    $periodicities = array('daily', 'weekly', 'monthly');

    if (!in_array($periodicity, $periodicities)) {
        return array( 
            'success' => false, 'error' => 'Invalid periodicity',
            'periodicity' => $periodicity );
    }

    update_option( 'pause_shop_periodicity', $periodicity );
    return array( 'success' => true );
}

function pause_shop_is_valid_date($date_str) {
    $date = DateTime::createFromFormat('Y-m-d', $date_str);

    return $date->format('Y-m-d') === $date_str;
}

function pause_shop_set_begin_date() {
    $begin_date = sanitize_text_field($_POST['begin_date']);

    if (!pause_shop_is_valid_date($begin_date)) {
        return array( 
            'success' => false, 'error' => 'Invalid begin date' );
    }

    update_option( 'pause_shop_begin_date', $begin_date );
    return array( 'success' => true );
}

function pause_shop_register_rest_routes() {
    register_rest_route( 'pause_shop/v0', '/pause_shop', array(
        'methods' => 'POST',
        'callback' => 'pause_shop_activate_on_demand_pause',
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'pause_shop/v0', '/unpause_shop', array(
        'methods' => 'POST',
        'callback' => 'pause_shop_deactivate_on_demand_pause',
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'pause_shop/v0', '/is_on_demand_paused', array(
        'methods' => 'GET',
        'callback' => function () {
            return array( 'paused' => get_option('pause_shop_on_demand_paused') );
        },
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'pause_shop/v0', '/is_schedule_paused', array(
        'methods' => 'GET',
        'callback' => function () {
            return array( 'paused' => get_option('pause_shop_schedule_paused') );
        },
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'pause_shop/v0', '/set_timezone', array(
        'methods' => 'POST',
        'callback' => 'pause_shop_set_timezone',
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'pause_shop/v0', '/get_timezone', array(
        'methods' => 'GET',
        'callback' => function () {
            return array( 'timezone' => get_option('pause_shop_timezone') );
        },
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'pause_shop/v0', '/set_begin_time', array(
        'methods' => 'POST',
        'callback' => 'pause_shop_set_begin_time',
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'pause_shop/v0', '/get_begin_time', array(
        'methods' => 'GET',
        'callback' => function () {
            return array( 'begin_time' => get_option('pause_shop_begin_time') );
        },
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'pause_shop/v0', '/set_end_time', array(
        'methods' => 'POST',
        'callback' => 'pause_shop_set_end_time',
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'pause_shop/v0', '/get_end_time', array(
        'methods' => 'GET',
        'callback' => function () {
            return array( 'end_time' => get_option('pause_shop_end_time') );
        },
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'pause_shop/v0', '/enable_scheduled_pause', array(
        'methods' => 'POST',
        'callback' => 'pause_shop_enable_scheduled_pause',
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'pause_shop/v0', '/disable_scheduled_pause', array(
        'methods' => 'POST',
        'callback' => 'pause_shop_disable_scheduled_pause',
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'pause_shop/v0', '/is_scheduled_pause_enabled', array(
        'methods' => 'GET',
        'callback' => function () {
            return array( 'scheduled_pause_enabled' => get_option('pause_shop_scheduled_pause_enabled') );
        },
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'pause_shop/v0', '/set_periodicity', array(
        'methods' => 'POST',
        'callback' => 'pause_shop_set_periodicity',
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'pause_shop/v0', '/get_periodicity', array(
        'methods' => 'GET',
        'callback' => function () {
            return array( 'periodicity' => get_option('pause_shop_periodicity') );
        },
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'pause_shop/v0', '/set_begin_date', array(
        'methods' => 'POST',
        'callback' => 'pause_shop_set_begin_date',
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'pause_shop/v0', '/get_begin_date', array(
        'methods' => 'GET',
        'callback' => function () {
            return array( 'begin_date' => get_option('pause_shop_begin_date') );
        },
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );
}

add_action('rest_api_init', 'pause_shop_register_rest_routes');

/* CSS */

function pause_shop_enqueue_styles() {
    wp_enqueue_style( 'pause-shop-style', plugins_url( 'pause-shop.css', __FILE__ ) );
}

add_action( 'admin_enqueue_scripts', 'pause_shop_enqueue_styles' );
