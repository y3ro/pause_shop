<?php
/*
Plugin Name: Pause shop
Description: Disable add-to-cart and checkout, disabling creating new orders, and show a notice. For Woocommerce.
Author: y3ro
Domain Path: /languages
Text Domain: pause-shop
Version: 0.7.0
License: MIT
*/

load_plugin_textdomain( 'pause-shop', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

function add_to_cart_disabled_msg() {
    $loc_msg = __( 'The purchase function has been disabled while we are performing maintenance on the site. We will be back shortly.', 
                   'pause-shop' );

    ?>
	<div class="pause-msg-at-product">
        <?php echo $loc_msg; ?>
    </div>
    <?php   
}

function filter_order_button_html() {
    $loc_msg = __( 'The purchase function has been disabled while we are performing maintenance on the site. We will be back shortly.', 
                   'pause-shop' );
    $html = sprintf('<div class="pause-msg-at-order">%s</div>', $loc_msg);

    return $html;
}

function block_order() {
    $loc_msg = __( 'The purchase function has been disabled while we are performing maintenance on the site. We will be back shortly.', 
                   'pause-shop' );
    wp_die($loc_msg);  // TODO: what is the right format?
}

function is_pause_day() {
    $periodicity = get_option('periodicity') ?: 'daily';
    $begin_date = get_option('begin_date') ?: '2000-01-01';
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

function is_scheduled_paused() {
    $timezone = date_default_timezone_get();
    date_default_timezone_set(get_option('timezone'));

    $scheduled_pause_enabled = get_option('scheduled_pause_enabled') ?: false;

    $begin_time = get_option('begin_time');
    $end_time = get_option('end_time');
    $time = date('H:i:s');

    $is_scheduled_paused = $scheduled_pause_enabled &&
        is_pause_day() &&    
        $time <= $end_time && $time >= $begin_time;

    date_default_timezone_set($timezone);

    return $is_scheduled_paused;
}

function pause_shop() {
    $on_demand_paused = get_option('on_demand_paused') ?: false;
    $schedule_paused = is_scheduled_paused();

    if ($on_demand_paused || $schedule_paused) {
		add_filter('woocommerce_is_purchasable', '__return_false');
		add_action('woocommerce_single_product_summary', 'add_to_cart_disabled_msg');
		add_filter('woocommerce_order_button_html', 'filter_order_button_html', 10, 2);
        add_action('woocommerce_before_checkout_process', 'block_order');
    }
}

add_action('wp', 'pause_shop');

/* Admin menu entry */

function pause_shop_menu() {
    add_menu_page(
        'Pause shop Settings',
        'Pause shop',
        'manage_options',
        'pause-shop-settings-group',
        'pause_shop_settings_page',
        'dashicons-controls-pause',
    );
}
add_action('admin_menu', 'pause_shop_menu');

/* Admin settings page */

function echo_pause_unpause_button() {
    $pause = get_option('on_demand_paused') ?: false;
    $scheduled_pause_enabled = get_option('scheduled_pause_enabled') ?: false;
    $schedule_paused = is_scheduled_paused();
    $timezone = get_option('timezone') ?: 'UTC';
    $begin_time = get_option('begin_time');
    $end_time = get_option('end_time');
    $periodicity = get_option('periodicity') ?: 'daily';
    $begin_date = get_option('begin_date');
    $pause_on_demand_title = __('Pause on demand', 'pause-shop');
    $pause_state_title = __('State', 'pause-shop');
    $pause_state = $pause ? __('Paused', 'pause-shop') : __('Unpaused', 'pause-shop');
    $pause_text = __('Pause shop', 'pause-shop');
    $unpause_text = __('Unpause shop', 'pause-shop');
    $button_text = $pause ? $unpause_text : $pause_text;
    ?>
    <h3><?php echo $pause_on_demand_title; ?></h3>
    <p>
        <?php echo $pause_state_title; ?>: <?php echo $pause_state; ?>
    </p>
    <form method="post" action="options.php" class="pause-on-demand-form">
        <?php settings_fields('pause-shop-settings-group'); ?>
        <?php do_settings_sections('pause-shop-settings-group'); ?>
        <input type="hidden" name="on_demand_paused" class="button button-primary" 
            value="<?php echo esc_attr(!$pause); ?>">
        <input type="hidden" name="scheduled_pause_enabled" 
        value="<?php echo $scheduled_pause_enabled; ?>">
        <input type="hidden" name="schedule_paused" value="<?php echo $schedule_paused; ?>">
        <input type="hidden" name="timezone" value="<?php echo $timezone; ?>">
        <input type="hidden" name="begin_time" value="<?php echo $begin_time; ?>">
        <input type="hidden" name="end_time" value="<?php echo $end_time; ?>">
        <input type="hidden" name="periodicity" value="<?php echo $periodicity; ?>">
        <input type="hidden" name="begin_date" value="<?php echo $begin_date; ?>">
            <?php submit_button(
                $button_text, 'primary', 'submit', true, array()); ?>
    </form>
    <?php
}

function echo_scheduled_pause_controls() {
    $scheduled_pause_enabled_title = __('Enable scheduled pause', 'pause-shop');
    $pause = is_scheduled_paused();
    $on_demand_paused = get_option('on_demand_paused') ?: false;
    $pause_state_title = __('State', 'pause-shop');
    $pause_state = $pause ? __('Paused', 'pause-shop') : __('Unpaused', 'pause-shop');
    $scheduled_pause_enabled = get_option('scheduled_pause_enabled') ?: false;
    $scheduled_pause_enabled_checked_str = $scheduled_pause_enabled ? 'checked' : '';
    $timezone_title = __('Timezone', 'pause-shop');
    $begin_time_title = __('Begin time', 'pause-shop');
    $end_time_title = __('End time', 'pause-shop');
    $periodicity_title = __('Periodicity', 'pause-shop');
    $begin_date_title = __('Begin date', 'pause-shop');

    ?>
    <h3><?php echo __('Scheduled pause', 'pause-shop'); ?></h3>
        <p>
            <?php echo $pause_state_title; ?>: <?php echo $pause_state; ?>
        </p>
        <form method="post" action="options.php">
            <?php settings_fields('pause-shop-settings-group'); ?>
            <?php do_settings_sections('pause-shop-settings-group'); ?>
            <input type="hidden" name="on_demand_paused" value="<?php echo $on_demand_paused; ?>">
            <table class="form-table">
                <tr valign="top">
                    <input id="scheduled-pause-enabled" type="checkbox" name="scheduled_pause_enabled" 
                    <?php echo $scheduled_pause_enabled_checked_str; ?>>
                    <label for="scheduled_pause_enabled"><?php echo $scheduled_pause_enabled_title; ?></label>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php echo $timezone_title; ?></th>
                    <td>
                        <select name="timezone" class="scheduled-pause-input">
                        <?php
                            $timezones = DateTimeZone::listIdentifiers();
                            foreach($timezones as $timezone) {
                                $selected_str = $timezone == get_option('timezone') ? 'selected' : '';
                                echo "<option value=\"$timezone\" $selected_str>$timezone</option>";
                            }
                        ?>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php echo $begin_time_title; ?></th>
                    <td>
                        <input type="time" name="begin_time" class="scheduled-pause-input"
                        value="<?php echo esc_attr(get_option('begin_time')); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php echo $end_time_title; ?></th>
                    <td>
                        <input type="time" name="end_time" class="scheduled-pause-input"
                        value="<?php echo esc_attr(get_option('end_time')); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php echo $periodicity_title; ?></th>
                    <td>
                        <select name="periodicity" class="scheduled-pause-input">
                        <?php
                            $periodicities = array('daily', 'weekly', 'monthly');
                            foreach($periodicities as $periodicity) {
                                $selected_str = $periodicity == get_option('periodicity') ? 'selected' : '';
                                echo "<option value=\"" . esc_attr($periodicity) . "\" $selected_str>" .
                                    __($periodicity, 'pause-shop') . "</option>";
                            }
                        ?>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php echo $begin_date_title; ?></th>
                    <td>
                        <input type="date" name="begin_date" class="scheduled-pause-input"
                        value="<?php echo esc_attr(get_option('begin_date')); ?>" />
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <?php
}

function get_all_endpoints_info() {
    $endpoints = array(
        "POST /wp-json/pause-shop/v0/pause_shop" => 
            __('Disable the add-to-cart and checkout buttons, and show a notice.', 'pause-shop'),
        "POST /wp-json/pause-shop/v0/unpause_shop" =>
            __('Enable the add-to-cart and checkout buttons, and hide the notice.', 'pause-shop'),
        "GET /wp-json/pause-shop/v0/is_on_demand_paused" =>
            __('Return the current on-demand pause status.', 'pause-shop'),
        "GET /wp-json/pause-shop/v0/is_scheduled_paused" =>
            __('Return the current scheduled pause status.', 'pause-shop'),
        "POST /wp-json/pause-shop/v0/set_timezone -d {\"timezone\": \"Europe/London\"}" =>
            '<div>' . __('Set timezone for the scheduled pause.', 'pause-shop') . '</div>',
        "GET /wp-json/pause-shop/v0/get_timezone" =>
            __('Get timezone for the scheduled pause.', 'pause-shop'),
        "POST /wp-json/pause-shop/v0/set_begin_time -d {\"begin_time\": \"01:00\"}" =>
            '<div>' . __('Set begin time for the scheduled pause.', 'pause-shop') . '</div>',
        "GET /wp-json/pause-shop/v0/get_begin_time" =>
            __('Get begin time for the scheduled pause.', 'pause-shop'),
        "POST /wp-json/pause-shop/v0/set_end_time -d {\"end_time\": \"01:30\"}" =>
            '<div>' . __('Set end time for the scheduled pause.', 'pause-shop') . '</div>',
        "GET /wp-json/pause-shop/v0/get_end_time" =>
            __('Get end time for the scheduled pause.', 'pause-shop'),
        "POST /wp-json/pause-shop/v0/set_periodicity -d {\"periodicity\": \"monthly\"}" => 
            '<div>' . __('Set periodicity for the scheduled pause.', 'pause-shop') . '</div>',
        "GET /wp-json/pause-shop/v0/get_periodicity" =>
            __('Get periodicity for the scheduled pause.', 'pause-shop'),
        "POST /wp-json/pause-shop/v0/set_begin_date -d {\"begin_date\": \"2020-01-01\"}" => 
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
class REST_Endpoints_Table extends WP_List_Table {

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
        $endpoints = get_all_endpoints_info();

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

function echo_help_text() {
    $help_title = __('Available REST endpoints', 'pause-shop');

    $wp_app_passwds_doc_link = "https://make.wordpress.org/core/2020/11/05/application-passwords-integration-guide/";
    $wp_app_passwds_doc_link_text = __('WordPress documentation', 'pause-shop');
    $wp_app_passwds_doc_a = "<a href=\"$wp_app_passwds_doc_link\" target=\"_blank\">$wp_app_passwds_doc_link_text</a>";
    $wp_app_passwds_text = sprintf(__("You can use application passwords to authenticate. See the %s for more information.", 'pause-shop'), 
                                   $wp_app_passwds_doc_a);
    
    $source_code_link = "https://github.com/y3ro/pause_shop";
    $source_code_link_text = __("Source code", "pause-shop");
    
    ?>
    <h3>
        <?php echo $help_title; ?>
    </h3>
    <?php
        // Create a new instance of our custom WP_List_Table subclass
        $table = new REST_Endpoints_Table();

        // Output the table on the admin page
        $table->prepare_items();
        $table->display();
    ?>
    <p>
        <?php echo $wp_app_passwds_text; ?>
    </p>
    <div class="pause-shop-source-link">
        <a class="pause-shop-source-button" 
         href="<?php echo $source_code_link; ?>" target="_blank">
            <span class="github-icon">
                <svg height="22" viewBox="0 0 16 16" width="32" aria-hidden="true">
                    <path fill="#fff" d="M8 0a8 8 0 0 0-8 8 8 8 0 0 0 5.312 7.594c.38.07.52-.164.52-.367 0-.18-.007-.82-.012-1.605-2.12.454-2.563-.508-2.563-.508-.345-.88-.842-1.114-.842-1.114-.687-.47.052-.46.052-.46.76.053 1.16.783 1.16.783.677 1.16 1.777.823 2.213.63.068-.494.266-.823.485-1.012-1.7-.195-3.488-.85-3.488-3.787 0-.837.3-1.524.793-2.056-.08-.196-.343-.976.076-2.03 0 0 .645-.207 2.107.785A7.36 7.36 0 0 1 8 4.354c.64.004 1.286.086 1.887.255 1.462-.992 2.107-.785 2.107-.785.42 1.054.158 1.834.08 2.03.495.532.792 1.218.792 2.055 0 2.944-1.79 3.59-3.497 3.78.274.237.518.704.518 1.416 0 1.022-.01 1.845-.01 2.096 0 .203.137.438.524.365A8.008 8.008 0 0 0 16 8a8 8 0 0 0-8-8z"/>
                </svg>
            </span>
            <span>
                <?php echo $source_code_link_text;?>
            </span>
        </a>
    </div>
    <?php    
}

function echo_donations_text() {
    $show_donations = true;  # this can only be changed here
    $donations_title = __('Donations', 'pause-shop');
    $ko_fi_link = 'https://ko-fi.com/y3ro752694';
    $ko_fi_msg = __('If you like this plugin and want me to keep working on it, please consider buying me a coffee :)', 'pause-shop');
    $ko_fi_btn_image_alt = esc_attr__('Buy Me a Coffee at ko-fi.com');
    
    if ($show_donations): ?>
        <div class="pause-shop-odd-section">
            <h3>
                <?php echo $donations_title ?>
            </h3>
            <p>
                <?php echo $ko_fi_msg; ?>
            </p>
            <a href="<?php echo $ko_fi_link; ?>" target="_blank">
                <img class="pause-shop-donations-button" 
                src="https://cdn.ko-fi.com/cdn/kofi1.png?v=2" 
                alt="<?php echo $ko_fi_btn_image_alt; ?>" />
            </a>
        </div>
    <?php endif;
}

function pause_shop_settings_page() {
    $settings_page_title = __('Pause shop Settings', 'pause-shop');
    ?>
    <div class="wrap">
        <h2><?php echo $settings_page_title; ?></h2>
        <div class="pause-shop-odd-section">
            <?php echo_pause_unpause_button(); ?>
        </div>
        <div>
            <?php echo_scheduled_pause_controls(); ?>
        </div>
    </div>
    <?php echo_donations_text(); ?>
    <div class="pause-shop-help">
        <?php echo_help_text(); ?>
    </div>
    <?php
}

/* Admin settings */

function pause_shop_register_settings() {
    register_setting('pause-shop-settings-group', 'timezone');
    register_setting('pause-shop-settings-group', 'begin_time');
    register_setting('pause-shop-settings-group', 'end_time');
    register_setting('pause-shop-settings-group', 'on_demand_paused');
    register_setting('pause-shop-settings-group', 'schedule_paused');
    register_setting('pause-shop-settings-group', 'scheduled_pause_enabled');
    register_setting('pause-shop-settings-group', 'begin_date');
    register_setting('pause-shop-settings-group', 'periodicity');
}
add_action('admin_init', 'pause_shop_register_settings');

/* REST endpoints */

function activate_on_demand_pause() {
    update_option( 'on_demand_paused', true );
    return array( 'success' => true );
}

function deactivate_on_demand_pause() {
    update_option( 'on_demand_paused', false );
    return array( 'success' => true );
}

function set_timezone() {
    $timezone = $_POST['timezone'];
    $timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);

    if (!in_array($timezone, $timezones)) {
        return array( 
            'success' => false, 'error' => 'Invalid timezone' );
    }

    update_option( 'timezone', $timezone );
    return array( 'success' => true );
}

function _is_valid_time($time_str) {
    return preg_match('/^([01][0-9]|2[0-3]):([0-5][0-9])$/', $time_str);
}

function set_begin_time() {
    $begin_time = $_POST['begin_time'];

    if (!_is_valid_time($begin_time)) {
        return array( 
            'success' => false, 'error' => 'Invalid date' );
    }

    update_option( 'begin_time', $begin_time );
    return array( 'success' => true );
}

function set_end_time () {
    $end_time = $_POST['end_time'];

    if (!_is_valid_time($end_time)) {
        return array( 
            'success' => false, 'error' => 'Invalid date' );
    }

    update_option( 'end_time', $end_time );
    return array( 'success' => true );
}

function enable_scheduled_pause() {
    update_option( 'scheduled_pause_enabled', true );
    return array( 'success' => true );
}

function disable_scheduled_pause() {
    update_option( 'scheduled_pause_enabled', false );
    return array( 'success' => true );
}

function set_periodicity() {
    $periodicity = $_POST['periodicity'];
    $periodicities = array('daily', 'weekly', 'monthly');

    if (!in_array($periodicity, $periodicities)) {
        return array( 
            'success' => false, 'error' => 'Invalid periodicity',
            'periodicity' => $periodicity );
    }

    update_option( 'periodicity', $periodicity );
    return array( 'success' => true );
}

function _is_valid_date($date_str) {
    $date = DateTime::createFromFormat('Y-m-d', $date_str);

    return $date->format('Y-m-d') === $date_str;
}

function set_begin_date() {
    $begin_date = $_POST['begin_date'];

    if (!_is_valid_date($begin_date)) {
        return array( 
            'success' => false, 'error' => 'Invalid date' );
    }

    update_option( 'begin_date', $begin_date );
    return array( 'success' => true );
}

function pause_shop_register_rest_routes() {
    register_rest_route( 'pause_shop/v0', '/pause_shop', array(
        'methods' => 'POST',
        'callback' => 'activate_on_demand_pause',
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'pause_shop/v0', '/unpause_shop', array(
        'methods' => 'POST',
        'callback' => 'deactivate_on_demand_pause',
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'pause_shop/v0', '/is_on_demand_paused', array(
        'methods' => 'GET',
        'callback' => function () {
            return array( 'paused' => get_option('on_demand_paused') );
        },
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'pause_shop/v0', '/is_schedule_paused', array(
        'methods' => 'GET',
        'callback' => function () {
            return array( 'paused' => get_option('schedule_paused') );
        },
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'pause_shop/v0', '/set_timezone', array(
        'methods' => 'POST',
        'callback' => 'set_timezone',
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'pause_shop/v0', '/get_timezone', array(
        'methods' => 'GET',
        'callback' => function () {
            return array( 'timezone' => get_option('timezone') );
        },
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'pause_shop/v0', '/set_begin_time', array(
        'methods' => 'POST',
        'callback' => 'set_begin_time',
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'pause_shop/v0', '/get_begin_time', array(
        'methods' => 'GET',
        'callback' => function () {
            return array( 'begin_time' => get_option('begin_time') );
        },
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'pause_shop/v0', '/set_end_time', array(
        'methods' => 'POST',
        'callback' => 'set_end_time',
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'pause_shop/v0', '/get_end_time', array(
        'methods' => 'GET',
        'callback' => function () {
            return array( 'end_time' => get_option('end_time') );
        },
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'pause_shop/v0', '/enable_scheduled_pause', array(
        'methods' => 'POST',
        'callback' => 'enable_scheduled_pause',
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'pause_shop/v0', '/disable_scheduled_pause', array(
        'methods' => 'POST',
        'callback' => 'disable_scheduled_pause',
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'pause_shop/v0', '/is_scheduled_pause_enabled', array(
        'methods' => 'GET',
        'callback' => function () {
            return array( 'scheduled_pause_enabled' => get_option('scheduled_pause_enabled') );
        },
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'pause_shop/v0', '/set_periodicity', array(
        'methods' => 'POST',
        'callback' => 'set_periodicity',
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'pause_shop/v0', '/get_periodicity', array(
        'methods' => 'GET',
        'callback' => function () {
            return array( 'periodicity' => get_option('periodicity') );
        },
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'pause_shop/v0', '/set_begin_date', array(
        'methods' => 'POST',
        'callback' => 'set_begin_date',
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'pause_shop/v0', '/get_begin_date', array(
        'methods' => 'GET',
        'callback' => function () {
            return array( 'begin_date' => get_option('begin_date') );
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

add_action( 'init', 'pause_shop_enqueue_styles' );

/* TODO: JS */