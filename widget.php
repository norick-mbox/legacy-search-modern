<?php
/*
 * Copyright 2015 Web Hammer UK Ltd.
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once dirname(__FILE__) . '/functions.php';

class WPCustomFieldsSearchWidget extends WP_Widget
{
    public function __construct()
    {
        parent::__construct('legacy-search-modern',
            __("Customisable search form for Legacy Search Modern", "legacy-search-modern"),
            array(
                "description" => __("Customisable search form for Legacy Search Modern", "legacy-search-modern"),
            )
        );
    }

    public function get_query_if_submitted($instance)
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $submitted = isset($_GET['wpcfs'])
        ? sanitize_text_field(wp_unslash($_GET['wpcfs']))
        : '';
// phpcs:enable WordPress.Security.NonceVerification.Recommended

        $widget_id = (
            is_array($instance) &&
            isset($instance['widget_id'])
        )
        ? (string) $instance['widget_id']
        : '';

        if ($submitted !== '' && $submitted === $widget_id) {
            // phpcs:disable WordPress.Security.NonceVerification.Recommended
            return stripslashes_deep(wp_unslash($_GET));
            // phpcs:enable WordPress.Security.NonceVerification.Recommended
        }

        return array();

    }
    public function widget($args, $instance)
    {
        require_once "search_form.php";
        $data_raw = isset($instance['data']) && is_string($instance['data'])
        ? $instance['data']
        : '';

        $data = $data_raw !== ''
        ? json_decode($data_raw, true)
        : array();

        if (!is_array($data)) {
            $data = array();
        }

        $widget_id = isset($args['widget_id'])
        ? $args['widget_id']
        : '';

        WPCFSSearchForm::show_form($data, $widget_id, $args);

    }

    public function update($new_instance, $old_instance)
    {
        $data = isset($new_instance['data']) && is_string($new_instance['data'])
        ? $new_instance['data']
        : '';

        return array(
            'data' => wpcfs_strip_hash_keys($data),
        );

    }

    public function form($instance)
    {

        $defaults = array();

        $instance = is_array($instance)
        ? array_merge($defaults, $instance)
        : $defaults;

        $settings_pages = apply_filters(
            'wpcfs_settings_pages',
            array()
        );

        $form_id = $this->get_field_id('edit-form');
        $form_id_attr = esc_attr($form_id);
        $form_id_js = esc_js($form_id);
        $field_name = esc_attr($this->get_field_name('data'));
        $plugin_root = esc_url(plugin_dir_url(__FILE__));
        $building_blocks_json = wp_json_encode(
            WPCustomFieldsSearchPlugin::get_javascript_editor_config()
        );

        $settings_pages_json = wp_json_encode($settings_pages);

        $building_blocks = wp_json_encode(
            WPCustomFieldsSearchPlugin::get_javascript_editor_config()
        );

        $settings_pages_json = wp_json_encode($settings_pages);

        $default = "{inputs:[],settings:{}}";
        $form_config = (
            isset($instance['data']) &&
            is_string($instance['data']) &&
            $instance['data'] !== ''
        )
        ? $instance['data']
        : $default;

        $decoded_form_config = json_decode($form_config, true);

        if (!is_array($decoded_form_config)) {
            $form_config2 = str_replace('""', '"', $form_config);
            $decoded_form_config2 = json_decode($form_config2, true);

            if (is_array($decoded_form_config2)) {
                $form_config = $form_config2;
            } else {
                $form_config = $default;
            }

        }
        include dirname(__FILE__) . '/templates/unsupported-message.php';
        // TODO: Could this be implemented with is_active_sidebar???
        $widget_number = property_exists($this, 'number')
        ? $this->number
        : null;

        if ($widget_number === '__i__') {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo "
				<div id='{$form_id_attr}' class='legacy-search-modern-form'>
				</div>
				<script>
                    var configure_forms = function(){
                        jQuery('.legacy-search-modern-form:not(.wpcfs_editor)').each(function(el){
                            var $=jQuery;
                            var template_id = '{$form_id_attr}',
                                template_name='{$field_name}',
                                id_parts = template_id.split('__i__'),
                                actual_id = $(this).attr('id');

                            var index=actual_id.substr(id_parts[0].length,actual_id.length-id_parts[1].length-id_parts[0].length);
                            var actual_name = template_name.replace('__i__',index);

                            if(index=='__i__') return;

                           $(this).wpcfs_editor({
                                'form_config': $form_config,
                                'building_blocks': {$building_blocks},
                                'settings_pages': {$settings_pages_json},
                                'field_name':'{$field_name}',
                                'root':'{$plugin_root}'
                            });

                        });
                    };
                    var __translations = {};
                    var __ = function(phrase){
                        return __translations[phrase]||phrase;
                    };
                    jQuery.get(
    ajaxurl + '?action=wpcfs_ng_load_translations&nonce=' + encodeURIComponent(wpcfsAdmin.adminNonce)
).then(function(data){
                       __translations = data;
                        configure_forms();
                        jQuery('body').mouseup(function(){
                            configure_forms();
                            setTimeout(configure_forms,1000);
                            setTimeout(configure_forms,5000);
                            setTimeout(configure_forms,10000);
                        });
                    });
				</script>
			";
        } else {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo "
    <div id='{$form_id_attr}' class='legacy-search-modern-form'>
    </div>
    <script>
        jQuery('#{$form_id_js}').wpcfs_editor({
            'form_config':$form_config,
            'building_blocks': {$building_blocks_json},
            'settings_pages': {$settings_pages_json},
            'field_name':'{$field_name}',
            'root':'{$plugin_root}'
        });
    </script>
";

        }
    }
}
