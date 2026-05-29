<?php
/*
Plugin Name: Legacy Search Modern
Description: Modernized fork of WP Custom Fields Search
Author URI: https://plugins.norick-mbox.com/
Plugin URI: https://github.com/norick-mbox/legacy-search-modern
Version: 1.0.2
Author: norick-mbox
Text Domain: legacy-search-modern
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

require_once __DIR__ . '/vendor/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$updateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/norick-mbox/legacy-search-modern',
    __FILE__,
    'legacy-search-modern'
);

$updateChecker->setBranch('main');



if (!defined('ABSPATH')) {
    exit;
}

if (!defined('LSM_OPTION_NAME')) {
    define('LSM_OPTION_NAME', 'legacy_search_modern_options');
}
if (!defined('LSM_LEGACY_OPTION_KEY')) {
    define('LSM_LEGACY_OPTION_KEY', 'wp_custom_fields_search');
}

/**
 * Try to deactivate the original plugin before loading this plugin's main code.
 */
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Legacy compatibility helper.
function lsm_try_deactivate_legacy_plugin_early()
{
    if (!is_admin()) {
        return false;
    }

    if (!function_exists('is_plugin_active') || !function_exists('deactivate_plugins') || !function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $plugins = get_plugins();

    foreach ($plugins as $plugin_file => $plugin_data) {
        if (
            strpos($plugin_file, 'wp-custom-fields-search/') === 0 &&
            is_plugin_active($plugin_file)
        ) {
            deactivate_plugins($plugin_file, true);

            set_transient(
                'legacy_search_modern_deactivated_old_plugin',
                true,
                60
            );

            return true;
        }
    }

    return false;
}

/**
 * If the original plugin's class already exists, it means the original plugin
 * is loaded in this request. We must not load the fork's main implementation yet.
 */
if (class_exists('WPCustomFieldsSearchPlugin', false)) {
    lsm_try_deactivate_legacy_plugin_early();
    return;
}

register_activation_hook(__FILE__, function () {
    lsm_try_deactivate_legacy_plugin_early();
});

add_action('admin_notices', function () {
    if (get_transient('legacy_search_modern_deactivated_old_plugin')) {
        delete_transient('legacy_search_modern_deactivated_old_plugin');

        echo '<div class="notice notice-warning is-dismissible"><p>';
        echo esc_html__(
            'WP Custom Fields Search has been deactivated automatically to avoid conflicts. You can import its settings below.',
            'legacy-search-modern'
        );
        echo '</p></div>';
    }
});

require_once plugin_dir_path(__FILE__) . 'plugin-main.php';
