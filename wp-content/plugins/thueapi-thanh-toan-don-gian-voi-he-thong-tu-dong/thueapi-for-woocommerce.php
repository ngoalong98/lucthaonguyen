<?php
/**
 * Plugin Name: ThueAPI for WooCommerce - Thanh toán đơn giản với hệ thống tự động !
 * Plugin URI: https://thueapi.com
 * Version: 1.0.6
 * Description: Giải pháp xử lý giao dịch tự động cho đơn hàng thanh toán bằng hình thức chuyển khoản qua các ngân hàng tại Việt Nam. Các ngân hàng thông dụng như: Vietcombank, Techcombank, ACB, Momo, MBBank, TPBank, VPBank...
 * Author: #CODETAY
 * Author URI: http://codetay.com
 * Tested up to: 5.7
 * WC tested up to: 5.1.0
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0
 */

defined('ABSPATH') or die('Code your dream');

if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

add_action('plugins_loaded', function () {
    require_once(plugin_basename('classes/wc-thueapi.php'));
}, 11);

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_script('thueapi', plugin_dir_url(__FILE__) . 'assets/js/app.js', [], '1.0.0', true);
    wp_enqueue_style('thueapi', plugin_dir_url(__FILE__) . 'assets/css/app.css', [], '1.0.0', 'all');
});

add_action('admin_enqueue_scripts', function () {
    wp_enqueue_script('thueapi', plugin_dir_url(__FILE__) . 'assets/js/app.admin.js', [], '1.0.0', true);
    wp_enqueue_style('thueapi', plugin_dir_url(__FILE__) . 'assets/css/app.admin.css', [], '1.0.0', 'all');
});

add_filter('woocommerce_payment_gateways', function ($gateways) {
    $gateways[] = 'ThueAPI_Gateway';
    return $gateways;
});

function addWcLessPaidPostStatus()
{
    register_post_status('wc-over-paid', [
        'label' => 'Thanh toán dư',
        'public' => true,
        'exclude_from_search' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('Thanh toán dư (%s)', 'Thanh toán dư (%s)')
    ]);

    register_post_status('wc-less-paid', [
        'label' => 'Thanh toán thiếu',
        'public' => true,
        'exclude_from_search' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('Thanh toán thiếu (%s)', 'Thanh toán thiếu (%s)')
    ]);
}

add_action('init', 'addWcLessPaidPostStatus');

add_filter('wc_order_statuses', function ($orderStatuses) {

    $newOrderStatuses = [];

    foreach ($orderStatuses as $key => $status) {
        $newOrderStatuses[$key] = $status;
    }

    $newOrderStatuses = array_merge($newOrderStatuses, [
        'wc-over-paid' => _('Thanh toán dư'),
        'wc-less-paid' => _('Thanh toán thiếu')
    ]);

    return $newOrderStatuses;
});

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $actionLinks = [
        'premium_plugins' => sprintf('<a href="https://codetay.com"  target="_blank" style="color: #e64a19; font-weight: bold; font-size: 108%%;" title="%s">%s</a>', _('Premium Plugins'), _('Premium Plugins')),
        'settings' => sprintf('<a href="%s" title="%s">%s</a>', admin_url('admin.php?page=wc-settings&tab=checkout&section=thueapi'), _('Thiết lập'), _('Thiết lập')),
    ];
    return array_merge($actionLinks, $links);
});