<?php

class WPCustomFieldsSearch_Equals extends WPCustomFieldsSearch_Comparison
{
    public function get_name()
    {return __("Exact Match", "legacy-search-modern");}
}
class WPCustomFieldsSearch_TextIn extends WPCustomFieldsSearch_Comparison
{
    public function get_name()
    {return __("Contains Text", "legacy-search-modern");}

    public function get_where($config, $value, $field_alias)
    {
        return sprintf(
            "%s LIKE '%%%s%%'",
            $field_alias,
            wpcfs_escape_string($value)
        );

    }
}
class WPCustomFieldsSearch_OrderedComparison extends WPCustomFieldsSearch_Comparison
{
    public function get_ordered_where($config, $value, $field_alias, $comparison)
    {
        $value = wpcfs_escape_string($value);
        switch ($config['numeric']) {
            case 'Numeric':
                $field_alias = "1*$field_alias";
                break;
            case 'Alphabetical':default:
                $value = "'$value'";
                break;
        }
        return "$field_alias$comparison$value";
    }
    public function get_editor_options()
    {
        $options = parent::get_editor_options();
        $options['extra_config_form'] = plugin_dir_url(__FILE__) . 'ng/partials/comparisons/numeric.html';
        $options['numeric'] = "Alphabetical";
        return $options;
    }
}
class WPCustomFieldsSearch_GreaterThan extends WPCustomFieldsSearch_OrderedComparison
{
    public function get_name()
    {return __("Greater Than", "legacy-search-modern");}
    public function get_where($config, $value, $field_alias)
    {
        $comparison = $config['inclusive'] ? ">=" : ">";
        return $this->get_ordered_where($config, $value, $field_alias, $comparison);
    }
}
class WPCustomFieldsSearch_LessThan extends WPCustomFieldsSearch_OrderedComparison
{
    public function get_name()
    {return __("Less Than", "legacy-search-modern");}

    public function get_where($config, $value, $field_alias)
    {
        $comparison = $config['inclusive'] ? "<=" : "<";
        return $this->get_ordered_where($config, $value, $field_alias, $comparison);
    }
}
class WPCustomFieldsSearch_Range extends WPCustomFieldsSearch_OrderedComparison
{
    public function get_name()
    {return __("In Range", "legacy-search-modern");}

    public function get_where($config, $value, $field_alias)
    {
        $range = explode(":", $value);
        if (count($range) != 2) {
            trigger_error(
                sprintf(
                    __("Range format should be '<min>:<max>' received '%s'", "legacy-search-modern"),
                    $value
                )
            );

            if (count($range) == 1) {
                $range[] = null;
            } else {
                $range = array_slice($range, 0, 2);
            }
        }

        list($min, $max) = $range;
        $params = array();
        if ($min !== null && $min !== '') {
            $comparison = $config['inclusive'] ? ">=" : ">";
            $params[] = $this->get_ordered_where($config, $min, $field_alias, $comparison);
        }
        if ($max !== null && $max !== '') {
            $comparison = $config['inclusive'] ? "<=" : "<";
            $params[] = $this->get_ordered_where($config, $max, $field_alias, $comparison);
        }
        if (!$params) {
            $params = array(1);
        }

        return "( " . join(" AND ", $params) . " )";
    }
}

class WPCustomFieldsSearch_SubCategoryOf extends WPCustomFieldsSearch_Comparison
{
    public function get_name()
    {return __("In category or Sub-category", "legacy-search-modern");}

    public function get_editor_options()
    {
        return array_merge(parent::get_editor_options(), array(
            "valid_for" => array(
                "datatype" => array("is_wp_term"),
            ),
        ));
    }
    public function collect_ids($field, $category_list)
    {
        $to_return = array();
        foreach ($category_list as $category) {
            $to_return[] = $category->$field;
            $to_return = array_unique(array_merge($to_return, $this->collect_ids($field, get_categories(array("child_of" => $category->term_id)))));
        }
        return $to_return;
    }
    public function get_where($config, $value, $field_alias)
    {
        global $wpdb;
        $field = $config['datatype_field'];
        if ($field == "term_id") {
            $dummy_category = new stdclass();
            $dummy_category->term_id = $value;
            $parent_categories = array($dummy_category);
        } else {
            $parent_categories = get_categories(array("name" => $value));
        }
        $child_categories = $this->collect_ids($field, $parent_categories);

        if (empty($child_categories)) {
            return '1 = 0';
        }

        return $field_alias . " IN ('" . implode("','", array_map('esc_sql', $child_categories)) . "')";

    }
}
