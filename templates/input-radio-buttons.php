<ul>
<?php
if (!defined('ABSPATH')) {
    exit;
}

$current = isset($query[$html_name]) ? (string) $query[$html_name] : '';

if (!empty($options['options']) && is_array($options['options'])) :
    foreach ($options['options'] as $option) :

        $value = isset($option['value']) ? (string) $option['value'] : '';
        $label = isset($option['label']) ? (string) $option['label'] : '';
        $id    = $html_name . '-' . $value;
?>
    <li>
        <input
            type="radio"
            name="<?php echo esc_attr($html_name); ?>"
            id="<?php echo esc_attr($id); ?>"
            value="<?php echo esc_attr($value); ?>"
            <?php checked($value, $current); ?>
        />

        <label for="<?php echo esc_attr($id); ?>">
            <?php echo esc_html($label); ?>
        </label>
    </li>
<?php
    endforeach;
endif;
?>
</ul>