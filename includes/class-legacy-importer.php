<?php

if (!defined('ABSPATH')) {
    exit;
}

class LSM_Legacy_Importer
{
    public static function maybe_import()
    {
        $already_imported = get_option('legacy_search_modern_imported', false);

        if ($already_imported) {
            return;
        }

        $legacy = get_option(LSM_LEGACY_OPTION_KEY);

        if (empty($legacy) || !is_array($legacy)) {
            update_option('legacy_search_modern_imported', 1);
            return;
        }

        $new_config = array(
            'presets' => array(),
        );

        if (!empty($legacy['presets']) && is_array($legacy['presets'])) {
            $new_config['presets'] = $legacy['presets'];
        }

        update_option(LSM_OPTION_NAME, $new_config);

        $legacy_widgets = get_option('widget_wp_custom_fields_search');

        if (!empty($legacy_widgets)) {
            update_option('legacy_search_modern_widgets', $legacy_widgets);
        }

        update_option('legacy_search_modern_imported', 1);
    }
}
