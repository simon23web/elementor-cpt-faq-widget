<?php

/**
 * Plugin Name: Elementor CPT FAQ Accordion
 * Description: CPT-powered FAQ accordion widget for Elementor, with FAQ schema output.
 * Version: 0.1.0
 * Author: 23Web
 * Author URI: https://www.23web.dev
 * Text Domain: elementor-cpt-faq-widget
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ECFW_VERSION', '0.1.0');
define('ECFW_PLUGIN_FILE', __FILE__);
define('ECFW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ECFW_PLUGIN_URL', plugin_dir_url(__FILE__));

function ecfw_load_textdomain()
{
    load_plugin_textdomain('elementor-cpt-faq-widget', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'ecfw_load_textdomain');

function ecfw_register_cpt()
{
    $labels = array(
        'name' => __('FAQs', 'elementor-cpt-faq-widget'),
        'singular_name' => __('FAQ', 'elementor-cpt-faq-widget'),
        'add_new' => __('Add New', 'elementor-cpt-faq-widget'),
        'add_new_item' => __('Add New FAQ', 'elementor-cpt-faq-widget'),
        'edit_item' => __('Edit FAQ', 'elementor-cpt-faq-widget'),
        'new_item' => __('New FAQ', 'elementor-cpt-faq-widget'),
        'view_item' => __('View FAQ', 'elementor-cpt-faq-widget'),
        'search_items' => __('Search FAQs', 'elementor-cpt-faq-widget'),
        'not_found' => __('No FAQs found', 'elementor-cpt-faq-widget'),
        'not_found_in_trash' => __('No FAQs found in Trash', 'elementor-cpt-faq-widget'),
        'all_items' => __('All FAQs', 'elementor-cpt-faq-widget'),
        'menu_name' => __('FAQs', 'elementor-cpt-faq-widget'),
        'name_admin_bar' => __('FAQ', 'elementor-cpt-faq-widget'),
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'has_archive' => 'faqs',
        'rewrite' => array('slug' => 'faqs'),
        'supports' => array('title', 'editor'),
        'menu_icon' => 'dashicons-editor-help',
    );

    register_post_type('ecfw_faq', $args);
}
add_action('init', 'ecfw_register_cpt');

function ecfw_admin_notice_missing_elementor()
{
    if (!current_user_can('activate_plugins')) {
        return;
    }

    $message = __('Elementor CPT FAQ Accordion requires Elementor to be installed and activated.', 'elementor-cpt-faq-widget');
    printf('<div class="notice notice-warning"><p>%s</p></div>', esc_html($message));
}

function ecfw_register_widget($widgets_manager)
{
    require_once ECFW_PLUGIN_DIR . 'includes/widgets/class-faq-accordion.php';
    $widgets_manager->register(new \ECFW\Widgets\FAQ_Accordion());
}

function ecfw_register_assets()
{
    wp_register_script(
        'ecfw-accordion',
        ECFW_PLUGIN_URL . 'assets/js/faq-accordion.js',
        array('jquery'),
        ECFW_VERSION,
        true
    );

    wp_register_style(
        'ecfw-accordion',
        ECFW_PLUGIN_URL . 'assets/css/faq-accordion.css',
        array(),
        ECFW_VERSION
    );
}

function ecfw_init_elementor_integration()
{
    if (!did_action('elementor/loaded')) {
        add_action('admin_notices', 'ecfw_admin_notice_missing_elementor');
        return;
    }

    add_action('elementor/widgets/register', 'ecfw_register_widget');
    add_action('elementor/frontend/after_register_scripts', 'ecfw_register_assets');
    add_action('elementor/frontend/after_register_styles', 'ecfw_register_assets');
}
add_action('plugins_loaded', 'ecfw_init_elementor_integration');
