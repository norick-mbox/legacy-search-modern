<?php
require_once dirname(__FILE__) . '/functions.php';

class WPCustomFieldsSearchValidationException extends Exception
{}
class WPCustomFieldsSearchPlugin
{
    public function __construct()
    {
        add_action('widgets_init', array($this, "widgets_init"));
        add_action('admin_enqueue_scripts', array($this, "admin_enqueue_scripts"));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));

        add_action('wp_ajax_wpcfs_angular_dependencies', array($this, 'angular_dependencies'));
        add_action('wp_ajax_wpcfs_save_preset', array($this, 'save_preset'));
        add_action('wp_ajax_wpcfs_delete_preset', array($this, 'delete_preset'));
        add_action('wp_ajax_wpcfs_export_settings', array($this, 'export_settings'));

        add_action('wp_ajax_wpcfs_ng_load_translations', array($this, 'ng_load_translations'));
        add_action('wp_ajax_wpcfs_ng_load_taxonomy', array($this, 'ng_load_taxonomy'));

        add_filter("wp_custom_fields_search_inputs", array($this, "wp_custom_fields_search_inputs"));
        add_filter("wp_custom_fields_search_datatypes", array($this, "wp_custom_fields_search_datatypes"));
        add_filter("wp_custom_fields_search_comparisons", array($this, "wp_custom_fields_search_comparisons"));
        add_filter("wpcfs_settings_pages", array($this, "wpcfs_settings_pages"), 9);

        add_shortcode("wp_custom_fields_search", array($this, "shortcode"));
        add_shortcode("wpcfs", array($this, "shortcode"));
        add_shortcode("legacy-search-modern", array($this, "shortcode"));

        add_shortcode("wpcfs-preset", array($this, "preset_shortcode"));
        add_shortcode("legacy-search-modern-preset", array($this, "preset_shortcode"));

        add_action('plugins_loaded', array($this, 'plugins_loaded'));

        if ($this->is_search_submitted()) {
            add_action("parse_query", array($this, "parse_query"));
            add_filter('template_include', array($this, 'show_search_results_template'), 11);
            add_filter('posts_orderby', array($this, 'posts_orderby'), 10, 2);
            add_filter('posts_join', array($this, 'posts_join'), 10, 2);
            add_filter('posts_where', array($this, 'posts_where'), 10, 2);
            add_filter('post_limits', array($this, 'post_limits'), 10, 2);
            add_filter('posts_groupby', array($this, 'posts_groupby'), 10, 2);
            add_filter('get_search_query', array($this, 'get_search_query'));
        }
    }

    public function is_search_submitted()
    {
        return !empty($_REQUEST['wpcfs']) && is_string($_REQUEST['wpcfs']);
    }

    public function get_submitted_form()
    {
        static $submitted;
        if (!isset($submitted)) {
            $wpcfs = array_key_exists('wpcfs', $_REQUEST) ? $_REQUEST['wpcfs'] : null;
            if (!$wpcfs) {
                $submitted = null;
            } elseif (substr($wpcfs, 0, 23) === "wp_custom_fields_search") {
                $options = get_option('legacy_search_modern_widgets', array());

                $widget_id = substr($wpcfs, 24);

                if (
                    isset($options[$widget_id]) &&
                    isset($options[$widget_id]['data'])
                ) {
                    $submitted = json_decode($options[$widget_id]['data'], true);
                } else {
                    $submitted = false;
                }

            } elseif (substr($wpcfs, 0, 7) === "preset-") {
                $config = get_option(LSM_OPTION_NAME, array(
                    'presets' => array(),
                ));

                $preset_id = substr($wpcfs, 7);

                $submitted = isset($config['presets'][$preset_id])
                ? $config['presets'][$preset_id]
                : false;

            } else {
                $submitted = false;
            }
            if (
                $submitted &&
                isset($submitted['inputs']) &&
                is_array($submitted['inputs'])
            ) {

                require_once dirname(__FILE__) . '/engine.php';
                $index = 0;
                foreach ($submitted['inputs'] as $k => &$input) {
                    try {
                        $input['datatype'] = wpcfs_instantiate_class($input['datatype']);
                        $input['input'] = wpcfs_instantiate_class($input['input']);
                        $input['comparison'] = wpcfs_instantiate_class($input['comparison']);
                    } catch (WPCustomFieldsSearchClassException $e) {
                        error_log("WP Custom Fields Search - get_submitted_form() " . $e->getMessage());
                        unset($submitted['inputs'][$k]);
                        continue;
                    }
                    $input['index'] = ++$index;
                }
            }
        }
        return $submitted;
    }
    public function show_search_results_template($template)
    {
        if (current_theme_supports('wp-block-styles')) {
            return $template;
        }
        $new_template = locate_template(array('wpcfs-search.php', 'search.php', 'templates/index.html', 'index.php'));
        return $new_template ?? $template;
    }

    public function should_override_current_query($wp_query)
    {
        $should_override = $wp_query->is_main_query() || ($wp_query->query_vars['wpcfs'] ?? false);
        return apply_filters('wpcfs_should_override_current_query', $should_override, $wp_query);
    }

    public function post_limits($limit, $wp_query)
    {
        if (!$this->should_override_current_query($wp_query)) {
            return $limit;
        }
        return $limit;
    }
    public function posts_orderby($orderby, $wp_query)
    {
        if (!$this->should_override_current_query($wp_query)) {
            return $orderby;
        }
        return $orderby;
    }
    public function posts_groupby($groupby, $wp_query)
    {
        if (!$this->should_override_current_query($wp_query)) {
            return $groupby;
        }
        return $groupby;
    }
    public function posts_join($join, $wp_query)
    {
        if (!$this->should_override_current_query($wp_query)) {
            return $join;
        }
        foreach ($this->get_submitted_inputs() as $input) {
            $isMulti = array_key_exists('multi_match', $input) && ($input['multi_match'] != "Any");
            $count = $isMulti ? count($input['input']->get_submitted_values($input, $_REQUEST)) : 1;
            $join = $input['datatype']->add_joins($input, $join, $count);
        }
        return $join;
    }
    public function posts_where($where, $wp_query)
    {
        if (!$this->should_override_current_query($wp_query)) {
            return $where;
        }
        $where = $this->open_up_post_types($where);
        $request = stripslashes_deep($_REQUEST);
        foreach ($this->get_submitted_inputs() as $input) {
            $submitted = $input['input']->get_submitted_values($input, $request);
            $isMulti = array_key_exists('multi_match', $input) && ($input['multi_match'] != "Any");
            $wheres = array();
            $join = $isMulti ? "AND" : "OR";
            $submitted_index = 0;
            foreach ($submitted as $value) {
                $sub_wheres = array();
                foreach ($input['datatype']->get_field_aliases($input, $submitted_index) as $alias) {
                    $sub_wheres[] = $input['comparison']->get_where($input, $value, $alias);
                }
                $wheres[] = "(" . join(" OR ", $sub_wheres) . ")";

                if ($isMulti) {
                    $submitted_index++;
                }
            }
            $where .= " AND ( " . join(" $join ", $wheres) . " )"; #TODO: Make the AND/OR configurable
        }
        return $where;
    }
    public function open_up_post_types($where)
    {
        global $wpdb;

        $form = $this->get_submitted_form();
        if (
            !$form ||
            !isset($form['settings']) ||
            !is_array($form['settings']) ||
            !array_key_exists('default_post_types', $form['settings'])
        ) {
            return $where;
        }

        if ($form['settings']['default_post_types'] !== false) {
            return $where;
        }

        $where = preg_replace(
            "/AND\s*$wpdb->posts.post_type\s*=\s*'[^']*'/",
            "",
            $where
        );
        $where = preg_replace(
            "/AND\s*$wpdb->posts.post_type\s*IN\s*\([^\)]*\)/",
            "",
            $where
        );

        $selected_post_types = isset($form['settings']['selected_post_types'])
        && is_array($form['settings']['selected_post_types'])
        ? $form['settings']['selected_post_types']
        : array();

        if (in_array("###ANY###", $selected_post_types) || !$selected_post_types) {
            return $where;
        }

        $options = [];
        foreach ($selected_post_types as $type) {
            $options[] = "'" . esc_sql($type) . "'";
        }
        $where .= " AND $wpdb->posts.post_type IN (" . join(",", $options) . ") ";

        return $where;
    }

    public function get_submitted_inputs()
    {
        $form = $this->get_submitted_form();
        if (!$form) {
            return array();
        }
        $inputs = array();
        if (!isset($form['inputs']) || !is_array($form['inputs'])) {
            return array();
        }

        foreach ($form['inputs'] as $input) {
            if ($input['input']->is_submitted($input, $_REQUEST)) {
                $inputs[] = $input;
            }

        }
        return $inputs;
    }

    public function get_search_query($query)
    {
        $description = array();
        foreach ($this->get_submitted_inputs() as $input) {
            $description[] = $this->describe_search($input);
        }
        return join(__(" &amp; ", "legacy-search-modern"), $description);

    }
    public function describe_search($input)
    {
        $label = $input['label'];
        $found = array();
        foreach ($input['input']->get_submitted_values($input, $_REQUEST) as $value) {
            $found[] = $input['comparison']->describe($label, $value);
        }
        $join = (
            isset($input['multi_match']) && $input['multi_match'] === 'Any'
        )
        ? __(" or ", "legacy-search-modern")
        : __(" &amp; ", "legacy-search-modern");

        return implode(" $join ", $found);
    }
    public function widgets_init()
    {
        require_once dirname(__FILE__) . '/widget.php';
        register_widget("WPCustomFieldsSearchWidget");
        wp_enqueue_style("wpcfs-form", plugin_dir_url(__FILE__) . 'templates/form.css');
    }

    public function get_angular_libraries()
    {
        return apply_filters("wpcfs_angular_libraries", array(
            array(
                "name" => "ng-sortable",
                "file" => plugin_dir_url(__FILE__) . "ng/lib/ui-sortable.js",
                "dependencies" => array(),
                "module_name" => "ui.sortable",
            ),
        ));
    }
    public function angular_dependencies()
    {
        check_ajax_referer('wpcfs_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Forbidden', '', array('response' => 403));
        }

        header('Content-Type: application/javascript; charset=utf-8');

        $libs = $this->get_angular_libraries();
        $module_names = array();

        foreach ($libs as $lib) {
            if (
                is_array($lib)
                && isset($lib['module_name'])
                && $lib['module_name'] !== ''
            ) {
                $module_names[] = sanitize_key($lib['module_name']);
            }
        }

        echo 'angular.module("WPCFS", ' . wp_json_encode($module_names) . ');';
        exit;
    }
    public function admin_enqueue_scripts()
    {
        $angular_libraries = $this->get_angular_libraries();
        $angular_dependencies = array("angularjs");

        foreach ($angular_libraries as $library) {
            if (!array_key_exists("dependencies", $library)) {
                $library["dependencies"] = array();
            }

            $library["dependencies"][] = "angularjs";
            wp_enqueue_script(
                $library["name"],
                $library["file"],
                $library["dependencies"]
            );
            $angular_dependencies[] = $library["name"];
        }
        wp_enqueue_script(
            'angularjs',
            plugin_dir_url(__FILE__) . 'js/angular.min.js',
            array('jquery'),
            '1.5.11',
            true
        );

        wp_enqueue_script(
            'ng-sortable',
            plugin_dir_url(__FILE__) . 'ng/lib/ui-sortable.js',
            array('angularjs'),
            filemtime(plugin_dir_path(__FILE__) . 'ng/lib/ui-sortable.js'),
            true
        );

        wp_enqueue_script(
            'wpcfs-services',
            plugin_dir_url(__FILE__) . 'ng/js/services.js',
            array('angularjs'),
            filemtime(plugin_dir_path(__FILE__) . 'ng/js/services.js'),
            true
        );

        wp_enqueue_script(
            'wpcfs-app',
            plugin_dir_url(__FILE__) . 'ng/js/app.js',
            array('angularjs', 'wpcfs-services', 'ng-sortable'),
            filemtime(plugin_dir_path(__FILE__) . 'ng/js/app.js'),
            true
        );

        wp_enqueue_script(
            'wpcfs-editor',
            plugin_dir_url(__FILE__) . 'js/wp-custom-fields-search-editor.js',
            array(
                'jquery',
                'jquery-ui-core',
                'jquery-ui-widget',
                'jquery-ui-mouse',
                'jquery-ui-sortable',
                'jquery-ui-draggable',
                'angularjs',
                'wpcfs-app',
            ),
            filemtime(plugin_dir_path(__FILE__) . 'js/wp-custom-fields-search-editor.js'),
            true
        );

        wp_enqueue_script(
            'wp-handlers',
            plugin_dir_url(__FILE__) . 'js/wp-handlers.js',
            array('wpcfs-editor'),
            filemtime(plugin_dir_path(__FILE__) . 'js/wp-handlers.js'),
            true
        );
        $config = get_option(LSM_OPTION_NAME, array(
            'presets' => array(),
        ));

        wp_localize_script(
            'wp-handlers',
            'wpcfsAdmin',
            array(
                'root' => plugin_dir_url(__FILE__),
                'presets' => isset($config['presets']) && is_array($config['presets'])
                ? $config['presets']
                : array(),
                'editor_config' => self::get_javascript_editor_config(),
                'settings_pages' => apply_filters('wpcfs_settings_pages', array()),
                'save_nonce' => wp_create_nonce('wpcfs_save_preset'),
                'delete_nonce' => wp_create_nonce('wpcfs_delete_preset'),
                'export_nonce' => wp_create_nonce('wpcfs_export_settings'),
                'importNonce' => wp_create_nonce('legacy_search_modern_import'),
                'adminNonce' => wp_create_nonce('wpcfs_admin_nonce'),
            )
        );

        wp_enqueue_style(
            'wpcfs-editor-style',
            plugin_dir_url(__FILE__) . 'ng/css/editor.css',
            array(),
            filemtime(plugin_dir_path(__FILE__) . 'ng/css/editor.css')
        );

        wp_register_style('wpcfs_css', plugin_dir_url(__FILE__) . 'ng/css/editor.css', false, '1.0.0');
        wp_register_style('wpcfs_bootstrap_css', plugin_dir_url(__FILE__) . 'ng/css/bootstrap-contained.css', false, '4.0.0');
        wp_enqueue_style('wpcfs_css');
        wp_enqueue_style('wpcfs_bootstrap_css');
    }

    public function admin_menu()
    {
        add_menu_page(
            'Legacy Search Modern',
            'Legacy Search Modern',
            'manage_options',
            'legacy-search-modern',
            array($this, 'presets_page')
        );

    }
    public function admin_init()
    {
        $previous_version = get_option("legacy_search_modern_version");
        $current_version = '0.1.0';
        if ($previous_version != $current_version) {
            $this->upgrade_plugin($previous_version, $current_version);
            update_option("legacy_search_modern_version", $current_version);
        }
    }
    public function plugins_loaded()
    {
        load_plugin_textdomain(
            'legacy-search-modern',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }

    public function upgrade_plugin($old_version, $latest_version)
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (!$old_version) {
            require_once dirname(__FILE__) . '/migrations/migrate-from-legacy-plugin.php';
            wpcfs_upgrade_3_x_to_1_0();
        }
    }

    public function presets_page()
    {
        if (!empty($_POST)) {
            // Save something...
            // Return
        }
        $config = get_option(LSM_OPTION_NAME, array(
            'presets' => array(),
        ));

        $presets = $config['presets'];
        include dirname(__FILE__) . '/templates/presets-page.php';

    }
    public function save_preset($data)
    {
        if (!(check_ajax_referer("wpcfs_save_preset", "nonce", false) && current_user_can('manage_options'))) {
            header("HTTP/1.1 403 Forbidden");
            throw new Exception("403 Forbidden");
        }

        try {
            $raw_data = isset($_POST['data'])
            ? wp_unslash($_POST['data'])
            : '';

            if ($raw_data === '') {
                throw new WPCustomFieldsSearchValidationException('data is required');
            }

            $data = json_decode(
                wpcfs_strip_hash_keys($raw_data),
                true
            );

            if (!is_array($data) || empty($data['id'])) {
                throw new WPCustomFieldsSearchValidationException('invalid preset');
            }

            $id = sanitize_key((string) $data['id']);

            $config = get_option(LSM_OPTION_NAME, array(
                'presets' => array(),
            ));

            if (
                !isset($config['presets']) ||
                !is_array($config['presets'])
            ) {
                $config['presets'] = array();
            }

            $config['presets'][$id] = $data;

            update_option(LSM_OPTION_NAME, $config);

            echo "OK";
        } catch (WPCustomFieldsSearchValidationException $e) {
            header("HTTP/1.1 400 Invalid Data");
            echo "Error {$e->getMessage()}";
            throw $e;
        } catch (Exception $e) {
            header("HTTP/1.1 500 Internal Error");
            echo "Error {$e->getMessage()}";
            throw $e;
        }
    }

    public function delete_preset($data)
    {
        if (!(check_ajax_referer("wpcfs_delete_preset", "nonce", false) && current_user_can('manage_options'))) {
            header("HTTP/1.1 403 Forbidden");
            throw new Exception("403 Forbidden");
        }

        $id = isset($_POST['id'])
        ? sanitize_key(wp_unslash($_POST['id']))
        : '';

        if ($id === '') {
            wp_send_json_error('invalid_id', 400);
        }

        $config = get_option(LSM_OPTION_NAME, array(
            'presets' => array(),
        ));

        if (
            isset($config['presets']) &&
            is_array($config['presets']) &&
            array_key_exists($id, $config['presets'])
        ) {
            unset($config['presets'][$id]);
        }

        update_option(LSM_OPTION_NAME, $config);
        echo "OK";
    }

    public function parse_query($wpquery)
    {
        if (!$this->should_override_current_query($wpquery)) {
            return;
        }
        $wpquery->is_search = true;
        $wpquery->is_home = false;
        $wpquery->is_page = false;
        $wpquery->is_singular = false;
        $wpquery->query_vars['pagename'] = null;
        $wpquery->query_vars['page_id'] = null;
        $wpquery->query_vars['paged'] = $wpquery->query_vars['page'] ?? null;
        $wpquery->query_vars['wpcfs'] = true;
    }
    public function show_search_template_for_searches($template)
    {
        if (!empty($_REQUEST['wpcfs'])) {
            $template = 'search';
        }
        return $template;
    }

    public static function get_javascript_editor_config()
    {
        $inputs = apply_filters("wp_custom_fields_search_inputs", array());
        $datatypes = apply_filters("wp_custom_fields_search_datatypes", array());
        $comparisons = apply_filters("wp_custom_fields_search_comparisons", array());

        foreach ($inputs as $k => $input) {
            $inputs[$k] = array(
                "id" => $input->get_id(),
                "name" => $input->get_name(),
                "options" => $input->get_editor_options(),
            );
        }
        foreach ($datatypes as $k => $datatype) {
            $datatypes[$k] = array(
                "id" => $datatype->get_id(),
                "name" => $datatype->get_name(),
                "options" => $datatype->get_editor_options(),
            );
        }
        foreach ($comparisons as $k => $comparison) {
            $comparisons[$k] = array(
                "id" => $comparison->get_id(),
                "name" => $comparison->get_name(),
                "options" => $comparison->get_editor_options(),
            );
        }

        return apply_filters("wp_custom_fields_search_editor_config", array(
            "inputs" => $inputs,
            "datatypes" => $datatypes,
            "comparisons" => $comparisons,
            "general" => array(
                "post_types" => array_keys(get_post_types()),
            ),
        ));
    }
    public function wp_custom_fields_search_inputs($inputs)
    {
        require_once dirname(__FILE__) . '/engine.php';
        $inputs = $inputs + array(
            new WPCustomFieldsSearch_TextBoxInput(),
            new WPCustomFieldsSearch_SelectInput(),
            new WPCustomFieldsSearch_CheckboxInput(),
            new WPCustomFieldsSearch_RadioButtons(),
            new WPCustomFieldsSearch_HiddenInput(),
        );
        return $inputs;
    }
    public function wp_custom_fields_search_datatypes($datatypes)
    {
        require_once dirname(__FILE__) . '/engine.php';
        $datatypes = $datatypes + array(
            new WPCustomFieldsSearch_PostField(),
            new WPCustomFieldsSearch_CustomField(),
            new WPCustomFieldsSearch_Category(),
            new WPCustomFieldsSearch_CustomTaxonomy(),
            new WPCustomFieldsSearch_Tag(),
        );
        return $datatypes;
    }
    public function wp_custom_fields_search_comparisons($comparisons)
    {
        require_once dirname(__FILE__) . '/engine.php';
        $comparisons = $comparisons + array(
            new WPCustomFieldsSearch_Equals(),
            new WPCustomFieldsSearch_TextIn(),
            new WPCustomFieldsSearch_GreaterThan(),
            new WPCustomFieldsSearch_LessThan(),
            new WPCustomFieldsSearch_Range(),
            new WPCustomFieldsSearch_SubCategoryOf(),
        );
        return $comparisons;
    }

    public function wpcfs_settings_pages($pages)
    {
        $pages[] = array(
            "title" => __('General', 'legacy-search-modern'),
            "template" => plugin_dir_url(__FILE__) . "ng/partials/settings-general.html",
        );
        return $pages;
    }
    public function preset_shortcode($atts)
    {
        $atts = shortcode_atts(array(
            "id" => "0",
        ), $atts);
        ob_start();
        $this->show_preset($atts['id']);
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }
    public function shortcode($atts)
    {
        $atts = shortcode_atts(array(
            "preset" => "0",
        ), $atts);

        ob_start();
        $this->show_preset($atts['preset']);
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    public function show_preset($id)
    {
        require_once dirname(__FILE__) . "/search_form.php";
        $preset = $this->get_preset_config($id);
        if (empty($preset)) {
            return;
        }

        include dirname(__FILE__) . '/templates/preset-display.php';
    }

    public function get_preset_config($id)
    {
        require_once dirname(__FILE__) . "/engine.php";
        if ($id === "default") {
            $id = 0;
        }

        $config = get_option(LSM_OPTION_NAME, array(
            'presets' => array(),
        ));

        if (!($config && array_key_exists('presets', $config) && array_key_exists($id, $config['presets']))) {
            trigger_error(__("No Such Preset", "legacy-search-modern") . " " . $id);

            return;
        }
        return $config['presets'][$id];
    }

    public function ng_load_translations()
    {
        check_ajax_referer('wpcfs_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => 'Forbidden',
            ), 403);
        }

        $files = apply_filters(
            'wpcfs_ng_translation_files',
            array(dirname(__FILE__) . '/ng/translations.php')
        );

        $all_translations = array();

        foreach ($files as $file) {
            if (!is_string($file) || !file_exists($file)) {
                continue;
            }

            $translations = array();

            require $file;

            if (!empty($translations) && is_array($translations)) {
                $all_translations = array_merge($all_translations, $translations);
            }
        }

        wp_send_json($all_translations);
    }

    public function recurseTaxonomy($name, $parent)
    {
        $terms = get_terms([
            'taxonomy' => $name,
            'parent' => $parent,
            'hide_empty' => false,
        ]);

        $output = [];
        foreach ($terms as $term) {
            $output[] = [
                "term_id" => $term->term_id,
                "name" => $term->name,
                "children" => $this->recurseTaxonomy($name, $term->term_id),
            ];
        }

        return $output;
    }
    public function ng_load_taxonomy()
    {
        check_ajax_referer('wpcfs_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => 'Forbidden',
            ), 403);
        }

        $taxonomy_name = isset($_GET['taxonomy'])
        ? sanitize_key(wp_unslash($_GET['taxonomy']))
        : '';

        if ($taxonomy_name === '' || !taxonomy_exists($taxonomy_name)) {
            wp_send_json_error(array(
                'message' => 'Invalid taxonomy',
            ), 400);
        }

        $terms = $this->recurseTaxonomy($taxonomy_name, 0);

        wp_send_json($terms);
    }

    public function export_settings()
    {
        check_ajax_referer('wpcfs_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => 'Forbidden',
            ), 403);
        }

        $export = array(
            'doc_type' => 'legacy-search-modern export',
            'plugin_version' => defined('WPCFS_PLUGIN_VERSION')
            ? WPCFS_PLUGIN_VERSION
            : '0.1.0',
            'format_version' => '1',
            'presets' => get_option(LSM_OPTION_NAME, array()),
            'widget' => get_option('legacy_search_modern_widgets', array()),
            'sidebars' => get_option('sidebars_widgets', array()),
        );

        wp_send_json($export);
    }

    public static function getInstance()
    {
        static $instance;
        if (!$instance) {
            $instance = new WPCustomFieldsSearchPlugin();
        }
        return $instance;
    }
}
require_once plugin_dir_path(__FILE__) . '/includes/class-legacy-importer.php';

register_activation_hook(__FILE__, function () {

    include_once ABSPATH . 'wp-admin/includes/plugin.php';

    $plugins = get_plugins();

    foreach ($plugins as $plugin_file => $plugin_data) {

        if (
            strpos($plugin_file, 'wp-custom-fields-search/') === 0 &&
            is_plugin_active($plugin_file)
        ) {
            deactivate_plugins($plugin_file);

            set_transient(
                'legacy_search_modern_deactivated_old_plugin',
                true,
                60
            );

            break;
        }
    }

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

WPCustomFieldsSearchPlugin::getInstance();
function wpcfs_show_preset($id)
{
    WPCustomFieldsSearchPlugin::getInstance()->show_preset($id);
}
if (!function_exists('wp_custom_fields_search')) {
    function wp_custom_fields_search($id = "default")
    {
        return wpcfs_show_preset($id);
    }
}

add_action('wp_ajax_legacy_search_modern_import', function () {

    check_ajax_referer('legacy_search_modern_import');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('permission');
    }

    require_once plugin_dir_path(__FILE__) . 'includes/class-legacy-importer.php';

    // 再インポートできるように imported フラグを一旦削除
    delete_option('legacy_search_modern_imported');

    LSM_Legacy_Importer::maybe_import();

    $result = get_option(LSM_OPTION_NAME);

    if (empty($result) || empty($result['presets'])) {
        wp_send_json_error('import_failed');
    }

    wp_send_json_success(array(
        'count' => count($result['presets']),
    ));
});
