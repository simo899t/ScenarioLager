<?php
/**
 * Plugin Name: Scenario Lager
 * Plugin URI: http://multipartner.dk/lager
 * Description: Equipment checkout and storage management system for Scenario
 * Version: 2.0.4
 * Author: Scenario
 * License: GPL v2 or later
 * Text Domain: scenario-lager
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SCENARIO_LAGER_VERSION', '2.1.0');
define('SCENARIO_LAGER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SCENARIO_LAGER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once SCENARIO_LAGER_PLUGIN_DIR . 'includes/database.php';
require_once SCENARIO_LAGER_PLUGIN_DIR . 'includes/shortcodes.php';

// Activation hook
register_activation_hook(__FILE__, 'scenario_lager_activate');

function scenario_lager_activate() {
    scenario_lager_create_tables();
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'scenario_lager_deactivate');

function scenario_lager_deactivate() {
    flush_rewrite_rules();
}

// Enqueue styles and scripts
add_action('wp_enqueue_scripts', 'scenario_lager_enqueue_assets');

function scenario_lager_enqueue_assets() {
    wp_enqueue_style('scenario-lager', SCENARIO_LAGER_PLUGIN_URL . 'assets/css/style.css', array(), SCENARIO_LAGER_VERSION);
}

// Handle form submissions early, before WordPress decides what page to show
add_action('init', 'scenario_lager_handle_form_submission');

function scenario_lager_handle_form_submission() {
    // Start session if not already started
    if (!session_id()) {
        session_start();
    }
    
    // Only process if user is logged in and it's our form
    if (empty($_SESSION['sl_logged_in']) || !isset($_POST['sl_action'])) {
        return;
    }
    
    // Verify nonce
    if (!isset($_POST['sl_nonce']) || !wp_verify_nonce($_POST['sl_nonce'], 'sl_action')) {
        return;
    }
    
    global $wpdb;
    
    if ($_POST['sl_action'] === 'add' && isset($_POST['sku'])) {
        $result = $wpdb->insert(
            $wpdb->prefix . 'sl_products',
            array(
                'sku' => sanitize_text_field($_POST['sku']),
                'name' => sanitize_text_field($_POST['name']),
                'description' => sanitize_textarea_field($_POST['description']),
                'location' => sanitize_text_field($_POST['location']),
                'is_available' => 1
            )
        );
        
        // Store result in session to display message
        if ($result === false) {
            $_SESSION['sl_message'] = array('type' => 'error', 'text' => 'Failed to add item: ' . $wpdb->last_error);
        } else {
            $_SESSION['sl_message'] = array('type' => 'success', 'text' => 'Item added successfully!');
        }
        
        // Redirect to inventory page
        $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $base_url = strtok($current_url, '?');
        wp_redirect($base_url);
        exit;
    }
}
?>
