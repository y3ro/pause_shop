<?php
/*
Plugin Name: Pause shop
Description: Disable add-to-cart and checkout, disabling creating new orders, and show a notice. For Woocommerce.
Author: y3ro
Domain Path: /languages
Text Domain: pause-shop
Version: 0.6.3
*/

load_plugin_textdomain( 'pause-shop', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

function add_to_cart_disabled_msg() {
    $loc_msg = __( 'The purchase function has been disabled while we are performing maintenance on the site. We will be back shortly.', 
                   'pause-shop' );

    ?>
	<div style="background-color: var(--wp--preset--color--luminous-vivid-amber); 
     color: white; font-weight: bold; border-radius: 16px; 
     text-align: center; padding: 4px 16px; margin: 8px 0;">
        <?php echo $loc_msg; ?>
    </div>
    <?php   
}

function filter_order_button_html() {
    $loc_msg = __( 'The purchase function has been disabled while we are performing maintenance on the site. We will be back shortly.', 
                   'pause-shop' );
    $html = sprintf('<div style="background-color: var(--wp--preset--color--luminous-vivid-amber); 
                  color: white; font-weight: bold; border-radius: 16px; text-align: center; 
                  padding: 4px 16px; margin-bottom: 16px;">%s</div>', $loc_msg);

    return $html;
}

function block_order() {
    $loc_msg = __( 'The purchase function has been disabled while we are performing maintenance on the site. We will be back shortly.', 
                   'pause-shop' );
    wp_die($loc_msg);  // TODO: what is the right format?
}

function is_pause_day() {
    $periodicity = get_option('periodicity') ?: 'daily';
    $begin_date_period = get_option('begin_date_period') ?: '2000-01-01';
    $today = date('Y-m-d');

    switch ($periodicity) {
        case 'daily':
            return true;
        case 'weekly':
            $weekday_number_period = date('N', strtotime($begin_date_period));
            $weekday_number_today = date('N', strtotime($today));
            return $weekday_number_period == $weekday_number_today;
        case 'monthly':
            $day_number_period = date('d', strtotime($begin_date_period));
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
    $paused = get_option('pause') ?: false;

    if ($paused || is_scheduled_paused()) {
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

// TODO: add readme

function echo_pause_unpause_button() {
    $pause = get_option('pause') ?: false;
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
    <form method="post" action="options.php">
        <?php settings_fields('pause-shop-settings-group'); ?>
        <?php do_settings_sections('pause-shop-settings-group'); ?>
        <input type="hidden" name="pause" class="button button-primary" 
            value="<?php echo esc_attr(!$pause); ?>">
            <?php submit_button(
                $button_text, 'primary', 'submit', true,
                array("style" => "font-size: 18px;")); ?>
    <?php
}

// TODO: add example data
function get_all_endpoints_info() {
    $endpoints = array(
        "[POST] " . get_rest_url(null, 'pause_shop/v0/pause_shop') => 
            __('Disable the add-to-cart and checkout buttons, and show a notice.', 'pause-shop'),
        "[POST] " . get_rest_url(null, 'pause_shop/v0/unpause_shop') =>
            __('Enable the add-to-cart and checkout buttons, and hide the notice.', 'pause-shop'),
        "[GET] " . get_rest_url(null, 'pause_shop/v0/is_paused') =>
            __('Return the current pause status.', 'pause-shop'),
        "[POST] " . get_rest_url(null, 'pause_shop/v0/set_timezone') =>
            __('Set timezone for the scheduled pause.', 'pause-shop'),
        "[GET] " . get_rest_url(null, 'pause_shop/v0/get_timezone') =>
            __('Get timezone for the scheduled pause.', 'pause-shop'),
        "[POST] " . get_rest_url(null, 'pause_shop/v0/set_begin_time') =>
            __('Set begin time for the scheduled pause.', 'pause-shop'),
        "[GET] " . get_rest_url(null, 'pause_shop/v0/get_begin_time') =>
            __('Get begin time for the scheduled pause.', 'pause-shop'),
        "[POST] " . get_rest_url(null, 'pause_shop/v0/set_end_time') =>
            __('Set end time for the scheduled pause.', 'pause-shop'),
        "[GET] " . get_rest_url(null, 'pause_shop/v0/get_end_time') =>
            __('Get end time for the scheduled pause.', 'pause-shop'),
        "[POST] " . get_rest_url(null, 'pause_shop/v0/enable_scheduled_pause') => 
            __('Enable the scheduled pause.', 'pause-shop'),
        "[POST] " . get_rest_url(null, 'pause_shop/v0/disable_scheduled_pause') =>
            __('Disable the scheduled pause.', 'pause-shop'),
        "[GET] " . get_rest_url(null, 'pause_shop/v0/is_scheduled_pause_enabled') =>
            __('Return the current scheduled pause status.', 'pause-shop'),
        "[POST] " . get_rest_url(null, 'pause_shop/v0/set_periodicity') => 
            __('Set periodicity for the scheduled pause.', 'pause-shop'),
        "[GET] " . get_rest_url(null, 'pause_shop/v0/get_periodicity') =>
            __('Get periodicity for the scheduled pause.', 'pause-shop'),
        "[POST] " . get_rest_url(null, 'pause_shop/v0/set_begin_date_period') => 
            __('Set begin date for the scheduled pause.', 'pause-shop'),
        "[GET] " . get_rest_url(null, 'pause_shop/v0/get_begin_date_period') =>
            __('Get begin date for the scheduled pause.', 'pause-shop'),
    );

    return $endpoints;
}

function echo_endpoints() {
    $endpoints = get_all_endpoints_info();

    foreach ($endpoints as $endpoint => $description) {
        ?>
        <tr>
            <td><?php echo $endpoint; ?></td>
            <td style="text-align: right;"><?php echo $description; ?></td>
        </tr>
        <?php
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
    <table>
        <tr>
            <th><?php _e('Endpoint', 'pause-shop'); ?></th>
            <th><?php _e('Description', 'pause-shop'); ?></th>
        </tr>
        <?php echo_endpoints(); ?>
    <p>
        <?php echo $wp_app_passwds_text; ?>
    </p>
    <p>
        <a href="<?php echo $source_code_link; ?>" target="_blank">
            <?php echo $source_code_link_text;?>
        </a>
    </p>
    <?php    
}

function echo_donations_text() {
    $show_donations = true;  # this can only be changed here
    $donations_title = __('Donations', 'pause-shop');
    $ko_fi_link = 'https://ko-fi.com/y3ro752694';
    $ko_fi_msg = __('If you like this plugin and want me to keep working on it, please consider buying me a coffee :)', 'pause-shop');
    $ko_fi_btn_image_alt = esc_attr__('Buy Me a Coffee at ko-fi.com');
    
    if ($show_donations): ?>
        <h3><?php echo $donations_title ?></h3>
        <p><?php echo $ko_fi_msg; ?></p>
        <a href="<?php echo $ko_fi_link; ?>" target="_blank">
            <img height="36" style="border:0px;height:36px;" 
            src="https://cdn.ko-fi.com/cdn/kofi1.png?v=2" 
            alt="<?php echo $ko_fi_btn_image_alt; ?>" />
        </a>
    <?php endif;
}

function pause_shop_settings_page() {
    $settings_page_title = __('Pause shop Settings', 'pause-shop');
    $scheduled_pause_enabled_title = __('Enable scheduled pause', 'pause-shop');
    $pause = is_scheduled_paused();
    $pause_state_title = __('State', 'pause-shop');
    $pause_state = $pause ? __('Paused', 'pause-shop') : __('Unpaused', 'pause-shop');
    $scheduled_pause_enabled = get_option('scheduled_pause_enabled') ?: false;
    $scheduled_pause_enabled_checked_str = $scheduled_pause_enabled ? 'checked' : '';
    $timezone_title = __('Timezone', 'pause-shop');
    $begin_time_title = __('Begin time', 'pause-shop');
    $end_time_title = __('End time', 'pause-shop');
    $periodicity_title = __('Periodicity', 'pause-shop');
    $begin_date_period_title = __('Begin date', 'pause-shop');

    ?>
    <div class="wrap">
        <h2><?php echo $settings_page_title; ?></h2>
        <div>
            <?php echo_pause_unpause_button(); ?>
        </div>
        <!-- TODO: to its own echo function -->
        <h3><?php echo __('Scheduled pause', 'pause-shop'); ?></h3>
        <p>
            <?php echo $pause_state_title; ?>: <?php echo $pause_state; ?>
        </p>
        <form method="post" action="options.php">
            <?php settings_fields('pause-shop-settings-group'); ?>
            <?php do_settings_sections('pause-shop-settings-group'); ?>
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
                    <th scope="row"><?php echo $begin_date_period_title; ?></th>
                    <td>
                        <input type="date" name="begin_date_period" class="scheduled-pause-input"
                        value="<?php echo esc_attr(get_option('begin_date_period')); ?>" />
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <div>
        <?php echo_donations_text(); ?>
    </div>
    <div>
        <?php echo_help_text(); ?>
    </div>
    <?php
}

/* Admin settings */

function pause_shop_register_settings() {
    register_setting('pause-shop-settings-group', 'timezone');
    register_setting('pause-shop-settings-group', 'begin_time');
    register_setting('pause-shop-settings-group', 'end_time');
    register_setting('pause-shop-settings-group', 'pause'); // TODO: rename to paused
    register_setting('pause-shop-settings-group', 'scheduled_pause_enabled');
    register_setting('pause-shop-settings-group', 'begin_date_period');
    register_setting('pause-shop-settings-group', 'periodicity');
}
add_action('admin_init', 'pause_shop_register_settings');

/* REST endpoints */

function activate_pause() {
    update_option( 'pause', true );
    return array( 'success' => true );
}

function deactivate_pause() {
    update_option( 'pause', false );
    return array( 'success' => true );
}

function set_timezone() {
    $timezone = $_POST['value'];
    $timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);

    if (!in_array($timezone, $timezones)) {
        return array( 
            'success' => false, 'error' => 'Invalid timezone' );
    }

    update_option( 'timezone', $timezone );
    return array( 'success' => true );
}

function _is_valid_time($time_str) {
    return preg_match('/^([01][0-9]|2[0-3]):([0-5][0-9])$/', $time_str); // TODO: test (and endpoints)
}

function set_begin_time() {
    $begin_time = $_POST['value'];

    if (!_is_valid_time($begin_time)) {
        return array( 
            'success' => false, 'error' => 'Invalid date' );
    }

    update_option( 'begin_time', $begin_time );
    return array( 'success' => true );
}

function set_end_time () {
    $end_time = $_POST['value'];

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
    $periodicity = $_POST['value'];
    $periodicities = array('daily', 'weekly', 'monthly');

    if (!in_array($periodicity, $periodicities)) {
        return array( 
            'success' => false, 'error' => 'Invalid periodicity' );
    }

    update_option( 'periodicity', $periodicity );
    return array( 'success' => true );
}

function _is_valid_date($date_str) {
    $date = DateTime::createFromFormat('Y-m-d', $date_str);

    return $date->format('Y-m-d') === $date_str;
}

function set_begin_date_period() {
    $begin_date_period = $_POST['value'];

    if (!_is_valid_date($begin_date_period)) {
        return array( 
            'success' => false, 'error' => 'Invalid date' );
    }

    update_option( 'begin_date_period', $begin_date_period );
    return array( 'success' => true );
}

function pause_shop_register_rest_routes() {
    register_rest_route( 'pause_shop/v0', '/pause_shop', array(
        'methods' => 'POST',
        'callback' => 'activate_pause',
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'pause_shop/v0', '/unpause_shop', array(
        'methods' => 'POST',
        'callback' => 'deactivate_pause',
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'pause_shop/v0', '/is_paused', array(
        'methods' => 'GET',
        'callback' => function () {
            return array( 'paused' => get_option('pause') );
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

    register_rest_route( 'pause_shop/v0', '/set_begin_date_period', array(
        'methods' => 'POST',
        'callback' => 'set_begin_date_period',
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_rest_route( 'pause_shop/v0', '/get_begin_date_period', array(
        'methods' => 'GET',
        'callback' => function () {
            return array( 'begin_date_period' => get_option('begin_date_period') );
        },
        'permission_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );
}

add_action('rest_api_init', 'pause_shop_register_rest_routes');
