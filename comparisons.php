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

        $numeric = isset($config['numeric'])
        ? $config['numeric']
        : 'Alphabetical';

        switch ($numeric) {
            case 'Numeric':
                $field_alias = "1*$field_alias";
                break;

            case 'Alphabetical':
            default:
                $value = "'$value'";
                break;
        }

        return "$field_alias $comparison $value";
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
        $inclusive = !empty($config['inclusive']);
        $comparison = $inclusive ? '>=' : '>';

        return $this->get_ordered_where($config, $value, $field_alias, $comparison);
    }
}
class WPCustomFieldsSearch_LessThan extends WPCustomFieldsSearch_OrderedComparison
{
    public function get_name()
    {return __("Less Than", "legacy-search-modern");}

    public function get_where($config, $value, $field_alias)
    {
        $inclusive = !empty($config['inclusive']);
        $comparison = $inclusive ? '<=' : '<';

        return $this->get_ordered_where($config, $value, $field_alias, $comparison);
    }
}
class WPCustomFieldsSearch_Range extends WPCustomFieldsSearch_OrderedComparison
{
    public function get_name()
    {return __("In Range", "legacy-search-modern");}

    public function get_where($config, $value, $field_alias)
    {
        $value = is_string($value) ? $value : '';
        $range = explode(':', $value, 2);
        if (count($range) != 2) {

            if (defined('WP_DEBUG') && WP_DEBUG) {

                trigger_error(
                    sprintf(
                        /* translators: %s is the invalid range value entered by the user. */
                        esc_html__(
                            "Range format should be '<min>:<max>' received '%s'",
                            'legacy-search-modern'
                        ),
                        esc_html($value)
                    ),
                    E_USER_WARNING
                );

            }

            if (count($range) === 1) {
                $range[] = null;
            } else {
                $range = array_slice($range, 0, 2);
            }
        }

        list($min, $max) = $range;
        $params = array();
        if ($min !== null && $min !== '') {
            $comparison = !empty($config['inclusive']) ? ">=" : ">";
            $params[] = $this->get_ordered_where($config, $min, $field_alias, $comparison);
        }
        if ($max !== null && $max !== '') {
            $comparison = !empty($config['inclusive']) ? "<=" : "<";
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
        if (
            empty($category_list) ||
            is_wp_error($category_list) ||
            !is_array($category_list)
        ) {
            return array();
        }

        $to_return = array();

        foreach ($category_list as $category) {
            if (!isset($category->$field)) {
                continue;
            }

            $to_return[] = $category->$field;

            $children = get_categories(array(
                'child_of' => isset($category->term_id) ? $category->term_id : 0,
            ));

            $to_return = array_unique(array_merge(
                $to_return,
                $this->collect_ids($field, $children)
            ));
        }

        return $to_return;
    }
    public function get_where($config, $value, $field_alias)
    {
        global $wpdb;
        $field = isset($config['datatype_field'])
        ? $config['datatype_field']
        : 'term_id';

        if ($field === "term_id") {
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
