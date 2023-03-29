<?php
/*
Plugin Name: Pause shop
Description: Disable add-to-cart and checkout, disabling creating new orders, and show a notice. 
Author: y3ro
Domain Path: /languages
Text Domain: pause-shop
Version: 0.4.3
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

function pause_shop() {
    $timezone = date_default_timezone_get();
    date_default_timezone_set(get_option('timezone'));

    $pause = get_option('pause') ?: false;
    $time_pause_enabled = get_option('time_pause_enabled') ?: false;

    $begin_time = get_option('begin_time');
    $end_time = get_option('end_time');
    $time = date('H:i:s');

    if ($pause || $time_pause_enabled && $time <= $end_time && $time >= $begin_time) {
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

// TODO: add REST endpoints for every possible action
// TODO: add readme

function echo_help_text() {
    $help_title = __('Available REST endpoints', 'pause-shop');

    $pause_endpoint = get_rest_url(null, 'pause_shop/v0/pause_shop');
    $unpause_endpoint = get_rest_url(null, 'pause_shop/v0/unpause_shop');
    $pause_help = __('This will disable the add-to-cart and checkout buttons, and show a notice.', 'pause-shop');
    $unpause_help = __('This will enable the add-to-cart and checkout buttons, and hide the notice.', 'pause-shop');
    $pause_curl = "curl --user \"USERNAME:PASSWORD\" -X POST $pause_endpoint";
    $unpause_curl = "curl --user \"USERNAME:PASSWORD\" -X POST $unpause_endpoint";

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
    <pre>
        <?php echo $pause_curl; ?>
    </pre>
    <p>
        <?php echo $pause_help; ?>
    </p>
    <pre>
        <?php echo $unpause_curl; ?>
    </pre>
    <p>
        <?php echo $unpause_help; ?>
    </p>
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
    $time_pause_enabled_title = __('Enable time pause', 'pause-shop');
    $time_pause_enabled = get_option('time_pause_enabled') ?: false;
    $time_pause_enabled_checked_str = $time_pause_enabled ? 'checked' : '';
    $timezone_title = __('Timezone', 'pause-shop');
    $begin_time_title = __('Begin time', 'pause-shop');
    $end_time_title = __('End time', 'pause-shop');

    ?>
    <div class="wrap">
        <h2><?php echo $settings_page_title; ?></h2>
        <form method="post" action="options.php">
            <?php settings_fields('pause-shop-settings-group'); ?>
            <?php do_settings_sections('pause-shop-settings-group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <input id="time-pause-enabled" type="checkbox" name="time_pause_enabled" 
                    <?php echo $time_pause_enabled_checked_str; ?>>
                    <label for="time_pause_enabled"><?php echo $time_pause_enabled_title; ?></label>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php echo $timezone_title; ?></th>
                    <td>
                        <select name="timezone" class="time-pause-input">
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
                        <input type="time" name="begin_time" class="time-pause-input"
                        value="<?php echo esc_attr(get_option('begin_time')); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php echo $end_time_title; ?></th>
                    <td>
                        <input type="time" name="end_time" class="time-pause-input"
                        value="<?php echo esc_attr(get_option('end_time')); ?>" />
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <div>
        <?php echo_help_text(); ?>
    </div>
    <div>
        <?php echo_donations_text(); ?>
    </div>
    <?php
}

/* Admin settings */

function pause_shop_register_settings() {
    register_setting('pause-shop-settings-group', 'timezone');
    register_setting('pause-shop-settings-group', 'begin_time');
    register_setting('pause-shop-settings-group', 'end_time');
    register_setting('pause-shop-settings-group', 'pause');
    register_setting('pause-shop-settings-group', 'time_pause_enabled');
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

add_action('rest_api_init', 'pause_shop_register_rest_routes');

/* JS */

// TODO: does not work
function pause_shop_enqueue_scripts() {
    wp_enqueue_script('pause_shop', plugins_url('/js/pause_shop.js', __FILE__ ));
}

add_action('wp_enqueue_scripts', 'pause_shop_enqueue_scripts');
