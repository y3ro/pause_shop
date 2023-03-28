<?php
/*
Plugin Name: Pause shop
Description: Disable add-to-cart and checkout, and show a notice, for a limited amount of time.
Author: y3ro
Domain Path: /languages
Text Domain: pause-shop
Version: 0.4.2
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
    wp_die($loc_msg);
}

function pause_shop() {
    $timezone = date_default_timezone_get();
    date_default_timezone_set(get_option('timezone'));

    $pause = get_option('pause') ?: false;

    $begin_time = get_option('begin_time');
    $end_time = get_option('end_time');
    $time = date('H:i:s');

    if (!$pause && (!$begin_time || !$end_time)) {
        return;
    }

    if ($pause || $time <= $end_time && $time >= $begin_time) {
		add_filter('woocommerce_is_purchasable', '__return_false');
		add_action('woocommerce_single_product_summary', 'add_to_cart_disabled_msg');
		add_filter('woocommerce_order_button_html', 'filter_order_button_html', 10, 2);
        add_action('woocommerce_before_checkout_process', 'block_order');
    }

    date_default_timezone_set($timezone);
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

// TODO: help in same flex-wrapped row as the settings
// TODO: add REST endpoints for every possible action
// TODO: no need for english localization files
// TODO: add a link to repo
// TODO: option to hide donations
// TODO: option to disable time-based pause
// TODO: add link to application passwords doc
// TODO: mention "disable orders" for SEO

function echo_help_text() {
    $pause_endpoint = get_rest_url(null, 'pause_shop/v0/pause_shop');
    $unpause_endpoint = get_rest_url(null, 'pause_shop/v0/unpause_shop');
    $pause_help = __('This will disable the add-to-cart and checkout buttons, and show a notice, for a limited amount of time.', 'pause-shop'); // TODO: more precisely, until disabled
    $unpause_help = __('This will enable the add-to-cart and checkout buttons, and hide the notice.', 'pause-shop');
    $pause_curl = "curl -X POST $pause_endpoint";
    $unpause_curl = "curl -X POST $unpause_endpoint";
    ?>
    <h3><?php echo __('Available REST endpoints', 'pause-shop'); ?></h3>
    <pre><?php echo $pause_curl; ?></pre>
    <p><?php echo $pause_help; ?></p>
    <pre><?php echo $unpause_curl; ?></pre>
    <p><?php echo $unpause_help; ?></p>
    <?php    
}

// TODO: move all __() to variables
function echo_donations_text() {
    $show_donations = true;  # this can only be changed here
    $ko_fi_link = 'https://ko-fi.com/y3ro752694';
    $ko_fi_msg = __('If you like this plugin and want me to keep working on it, please consider buying me a coffee :)', 'pause-shop');
    
    if ($show_donations): ?>
        <h3><?php echo __('Donations', 'pause-shop'); ?></h3>
        <p><?php echo $ko_fi_msg; ?></p>
        <a href="<?php echo $ko_fi_link; ?>" target="_blank"> <!-- TODO: use esc_url -->
            <img height="36" style="border:0px;height:36px;" 
            src="https://cdn.ko-fi.com/cdn/kofi1.png?v=2" 
            alt="<?php echo __('Buy Me a Coffee at ko-fi.com'); ?>" /> <!-- TODO: use esc_attr -->
        </a>
    <?php endif;
}

function pause_shop_settings_page() {
    ?>
    <div class="wrap">
        <h2><?php echo __('Pause shop Settings', 'pause-shop'); ?></h2>
        <form method="post" action="options.php">
            <?php settings_fields('pause-shop-settings-group'); ?>
            <?php do_settings_sections('pause-shop-settings-group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php echo __('Timezone', 'pause-shop'); ?></th>
                    <td>
                        <select name="timezone">
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
                    <th scope="row"><?php echo __('Begin time', 'pause-shop'); ?></th>
                    <td>
                        <input type="time" name="begin_time" 
                        value="<?php echo esc_attr(get_option('begin_time')); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php echo __('End time', 'pause-shop'); ?></th>
                    <td>
                        <input type="time" name="end_time" 
                        value="<?php echo esc_attr(get_option('end_time')); ?>" />
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <!-- TODO: styles to CSS -->
    <div style="
        display: flex;
        flex-wrap: wrap;">
        <div style="
            max-width: 30rem;
            overflow-wrap: anywhere;
            margin-right: 2rem;
            ">
            <?php echo_help_text(); ?>
        </div>
        <div style="
            max-width: 19rem;
            margin-left: 2rem;">
            <?php echo_donations_text(); ?>
        </div>
    </div>
    <?php
}

/* Admin settings */

function pause_shop_register_settings() {
    register_setting('pause-shop-settings-group', 'timezone');
    register_setting('pause-shop-settings-group', 'begin_time');
    register_setting('pause-shop-settings-group', 'end_time');
    register_setting('pause-shop-settings-group', 'pause');
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
}

add_action( 'rest_api_init', 'pause_shop_register_rest_routes' );
