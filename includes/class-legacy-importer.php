<?php

if (!defined('ABSPATH')) {
    exit;
}
if (!defined('LSM_OPTION_NAME')) {
    define('LSM_OPTION_NAME', 'legacy_search_modern_options');
}

if (!defined('LSM_LEGACY_OPTION_KEY')) {
    define('LSM_LEGACY_OPTION_KEY', 'wp_custom_fields_search');
}

class LSM_Legacy_Importer
{
    public static function maybe_import()
    {
        $already_imported = get_option('legacy_search_modern_imported', false);

        if ($already_imported) {
            return;
        }

        $legacy = get_option('wp_custom_fields_search');

        if (empty($legacy) || !is_array($legacy)) {
            $legacy = get_option('wp_custom_fields_search_options');
        }

        if (empty($legacy) || !is_array($legacy)) {
            $legacy = get_option(LSM_LEGACY_OPTION_KEY);
        }

        $new_config = array(
            'presets' => array(),
        );

        if (!is_array($legacy)) {
            $legacy = array();
        }

        if (!empty($legacy['presets']) && is_array($legacy['presets'])) {

            foreach ($legacy['presets'] as $preset_id => $preset) {

                $preset_id = sanitize_key((string) $preset_id);

                if ($preset_id === '' || !is_array($preset)) {
                    continue;
                }

                if (!isset($preset['inputs']) || !is_array($preset['inputs'])) {
                    $preset['inputs'] = array();
                }

                if (!isset($preset['settings']) || !is_array($preset['settings'])) {
                    $preset['settings'] = array();
                }

                $new_config['presets'][$preset_id] = $preset;
            }
        }

        update_option(LSM_OPTION_NAME, $new_config);

        $legacy_widgets = get_option('widget_wp_custom_fields_search');

        if (!empty($legacy_widgets) && is_array($legacy_widgets)) {
            update_option('legacy_search_modern_widgets', $legacy_widgets);
        }

        update_option('legacy_search_modern_imported', 1);
    }
}
