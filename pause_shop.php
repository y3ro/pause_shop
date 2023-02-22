<?php
/*
Plugin Name: Pause shop
Description: Disable add to cart and checkout, and show a notice, under certain conditions.
Author: Yerai Doval Mosquera
*/

function add_to_cart_disabled_msg() {
	echo '<div style="background-color: var(--wp--preset--color--luminous-vivid-amber); color: white; font-weight: bold; border-radius: 16px; text-align: center; padding: 4px 16px; margin: 8px 0;">La función de compra se ha deshabilitado mientras realizamos tareas de mantenimiento en el sitio. Volvemos en media hora.</div>';
}

function filter_order_button_html() {
    return '<div style="background-color: var(--wp--preset--color--luminous-vivid-amber); color: white; font-weight: bold; border-radius: 16px; text-align: center; padding: 4px 16px; margin-bottom: 16px;">La función de compra se ha deshabilitado mientras realizamos tareas de mantenimiento en el sitio. Volvemos en media hora.</div>';
}

function pause_store() {
    $timezone = date_default_timezone_get();
    date_default_timezone_set('Europe/Madrid');

    $begin_time = '08:00:00';
    $end_time = '08:30:00';
    $time = date('H:i:s');

    if ($time <= $end_time && $time >= $begin_time) {
		add_filter('woocommerce_is_purchasable', '__return_false');
		add_action('woocommerce_single_product_summary', 'add_to_cart_disabled_msg');
		add_filter('woocommerce_order_button_html', 'filter_order_button_html', 10, 2);
    }

    date_default_timezone_set($timezone);
}

add_action( 'wp', 'pause_store' );