<?php
require_once dirname(__FILE__) . '/functions.php';

class WPCustomFieldsSearch_Input
{
    public string $template = 'text';
    public bool $show_in_form = true;

    public function render($options, $query)
    {
        $template_file = apply_filters(
            'wpcfs_form_input',
            dirname(__FILE__) . '/templates/input-' . $this->template . '.php',
            $this->template,
            $options
        );

        $html_name = isset($options['html_name']) ? $options['html_name'] : '';
        $html_id = isset($options['html_id']) ? $options['html_id'] : '';

        if (is_string($template_file) && file_exists($template_file)) {
            include $template_file;
        }
    }

    public function get_id()
    {
        return get_class($this);
    }
    public function get_name()
    {
        return str_replace("WPCustomFieldsSearch_", "", get_class($this));
    }
    public function get_editor_options()
    {
        return array();
    }
    public function is_submitted($options, $data)
    {
        if (!is_array($data)) {
            return false;
        }

        $html_name = 'f' . (isset($options['index']) ? $options['index'] : '');

        return array_key_exists($html_name, $data) && $data[$html_name] !== '';
    }
    public function get_submitted_value($options, $data)
    {
        if (!is_array($data)) {
            return null;
        }

        $html_name = 'f' . (isset($options['index']) ? $options['index'] : '');

        return array_key_exists($html_name, $data)
        ? $data[$html_name]
        : null;
    }

    public function get_submitted_values($options, $data)
    {
        $value = $this->get_submitted_value($options, $data);

        if ($value === null || $value === '') {
            return array();
        }

        return is_array($value) ? $value : array($value);

    }
}

class WPCustomFieldsSearch_DataType
{
    public bool $multijoin = false;

    public function get_id()
    {
        return get_class($this);
    }
    public function get_name()
    {
        return str_replace("WPCustomFieldsSearch_", "", get_class($this));
    }
    public function get_editor_options()
    {
        return array(
            "all_fields" => $this->getFieldMap(),
        );
    }

    public function add_joins($config, $join, $count)
    {
        global $wpdb;
        if (!$this->multijoin) {
            $count = 1;
        }

        for ($index = 0; $index < $count; $index++) {
            $alias = $this->get_table_alias($config, $index);
            $posts_table = $wpdb->posts;
            $join .= " LEFT JOIN " . $this->get_table_name($config) . " AS $alias ON $alias.post_id = $posts_table.id";
        }
        return $join;
    }

    public function get_field_aliases($config, $count)
    {
        if (!is_array($config) || empty($config['datatype_field'])) {
            return array();
        }

        return array($this->get_field_alias($config, $config['datatype_field'], $count));
    }

    public function get_field_alias($config, $field_name, $count)
    {
        return $this->get_table_alias($config, $count) . "." . $field_name;
    }

    public function get_table_alias($config, $count)
    {
        if (!$this->multijoin) {
            $count = 1;
        }

        $index = isset($config['index']) ? (string) $config['index'] : '0';

        return 'wpcfs' . $index . '_' . $count;
    }

    public function _array_to_suggestions_list($array)
    {
        $return = array();

        if (!is_array($array)) {
            return $return;
        }

        foreach ($array as $value) {
            $return[] = array(
                'value' => $value,
                'label' => $value,
            );
        }

        return $return;
    }
}

class WPCustomFieldsSearch_Comparison
{
    public function get_id()
    {
        return get_class($this);
    }
    public function get_name()
    {
        return str_replace("WPCustomFieldsSearch_", "", get_class($this));
    }
    public function get_editor_options()
    {
        return array();
    }

    public function get_where($config, $value, $field_alias)
    {
        return sprintf(
            "%s = '%s'",
            $field_alias,
            wpcfs_escape_string($value)
        );

    }

    public function describe($label, $value)
    {
        return $label . " " . $this->get_name() . " " . $value;
    }
}

require_once dirname(__FILE__) . '/inputs.php';
require_once dirname(__FILE__) . '/datatypes.php';
require_once dirname(__FILE__) . '/comparisons.php';

do_action('wpcfs_engine_loaded');
