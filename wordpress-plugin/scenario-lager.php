<?php
/**
 * Plugin Name: Scenario Lager
 * Plugin URI: http://multipartner.dk/lager
 * Description: Equipment checkout and storage management system for Scenario
 * Version: 2.0.2
 * Author: Scenario
 * License: GPL v2 or later
 * Text Domain: scenario-lager
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SCENARIO_LAGER_VERSION', '2.0.2');
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
?>
