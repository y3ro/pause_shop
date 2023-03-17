<?php
/*
Plugin Name: Pause shop
Description: Disable add-to-cart and checkout, and show a notice, for a limited amount of time.
Author: Yerai Doval Mosquera
Version: 0.1.0
*/

function add_to_cart_disabled_msg() {
	echo '<div style="background-color: var(--wp--preset--color--luminous-vivid-amber); color: white; font-weight: bold; border-radius: 16px; text-align: center; padding: 4px 16px; margin: 8px 0;">La función de compra se ha deshabilitado mientras realizamos tareas de mantenimiento en el sitio. Volvemos en media hora.</div>';
}

function filter_order_button_html() {
    return '<div style="background-color: var(--wp--preset--color--luminous-vivid-amber); color: white; font-weight: bold; border-radius: 16px; text-align: center; padding: 4px 16px; margin-bottom: 16px;">La función de compra se ha deshabilitado mientras realizamos tareas de mantenimiento en el sitio. Volvemos en media hora.</div>';
}

function pause_shop() {
    $timezone = date_default_timezone_get();
    date_default_timezone_set(get_option('timezone')); // 'Europe/Madrid'

    $begin_time = get_option('begin_time'); // '08:00:00'
    $end_time = get_option('end_time'); // '08:30:00'
    $time = date('H:i:s');

    if (!$begin_time || !$end_time) {
        return;
    }

    if ($time <= $end_time && $time >= $begin_time) {
		add_filter('woocommerce_is_purchasable', '__return_false');
		add_action('woocommerce_single_product_summary', 'add_to_cart_disabled_msg');
		add_filter('woocommerce_order_button_html', 'filter_order_button_html', 10, 2);
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

function pause_shop_settings_page() {
    ?>
    <div class="wrap">
        <h2>Pause shop Settings</h2>
        <form method="post" action="options.php">
            <?php settings_fields('pause-shop-settings-group'); ?>
            <?php do_settings_sections('pause-shop-settings-group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Timezone</th>
                    <td><input type="text" name="timezone" value="<?php echo esc_attr(get_option('timezone')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Begin time</th>
                    <td><input type="text" name="begin_time" value="<?php echo esc_attr(get_option('begin_time')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">End time</th>
                    <td><input type="text" name="end_time" value="<?php echo esc_attr(get_option('end_time')); ?>" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/* Admin settings */

function pause_shop_register_settings() {
    register_setting('pause-shop-settings-group', 'timezone');
    register_setting('pause-shop-settings-group', 'begin_time');
    register_setting('pause-shop-settings-group', 'end_time');
}
add_action('admin_init', 'pause_shop_register_settings');
