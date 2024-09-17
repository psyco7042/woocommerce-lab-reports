<?php
/**
 * plugin name: woocommerce lab report
 * author: Priyam Sengupta
 * author uri: https://github.com/psyco7042
 * description: this allows the user to upload a lab report within a perticular order type that contains test results
 */

 if(!defined('ABSPATH')){
    header('Location: /');
    die('Cant Access');
 }
// check if the woocommerce is loaded or not
 function wlr_woo_check(){
    if(!class_exists('WooCommerce')){
        return;
    }
    include_once plugin_dir_path( __FILE__ ) . 'includes/admin-menu.php';
    include_once plugin_dir_path( __FILE__ ) . 'includes/front-end-admin-menu.php';
 }

 add_action('plugins_loaded', 'wlr_woo_check');

 wp_enqueue_style( 'main', plugins_url('/assets/css/main.css', __FILE__), '', filemtime(plugin_dir_path(__FILE__).'/assets/css/main.css'), 'all' );
 wp_enqueue_script('main', plugins_url('assets/js/main.js'), array('jquery'), filemtime(plugin_dir_path(__FILE__) . 'assets/js/main.js'), true);