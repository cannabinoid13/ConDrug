<?php
/**
 * Plugin Name: ConDrug
 * Plugin URI:  https://example.com/condrug
 * Description: Provides a workspace shortcode and foundation for future Stripe-powered features.
 * Version:     0.1.0
 * Author:      Your Name
 * Author URI:  https://example.com
 * Text Domain: condrug
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('CONDRUG_PLUGIN_FILE')) {
    define('CONDRUG_PLUGIN_FILE', __FILE__);
}

if (!defined('CONDRUG_PLUGIN_DIR')) {
    define('CONDRUG_PLUGIN_DIR', plugin_dir_path(CONDRUG_PLUGIN_FILE));
}

if (!defined('CONDRUG_PLUGIN_URL')) {
    define('CONDRUG_PLUGIN_URL', plugin_dir_url(CONDRUG_PLUGIN_FILE));
}

$vendorAutoload = CONDRUG_PLUGIN_DIR . 'vendor/autoload.php';
if (is_readable($vendorAutoload)) {
    require_once $vendorAutoload;
}

require_once CONDRUG_PLUGIN_DIR . 'includes/class-condrug-autoloader.php';

ConDrug\Autoloader::register();

register_activation_hook(CONDRUG_PLUGIN_FILE, function () {
    // Placeholder for future activation logic (e.g., Stripe setup, DB tables).
});

register_deactivation_hook(CONDRUG_PLUGIN_FILE, function () {
    // Placeholder for future deactivation logic.
});

add_action('plugins_loaded', function () {
    ConDrug\Plugin::boot();
});
