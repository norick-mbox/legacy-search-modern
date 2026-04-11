<?php 
 if (!defined('ABSPATH')) {
    exit;
}

?>

<select
    name="<?php echo esc_attr($html_name); ?>"
    id="<?php echo esc_attr($html_id); ?>"
>
<?php
$current = isset($query[$html_name]) ? (string) $query[$html_name] : '';

if (!empty($options['options']) && is_array($options['options'])) :
    foreach ($options['options'] as $option) :

        $value = isset($option['value']) ? (string) $option['value'] : '';
        $label = isset($option['label']) ? (string) $option['label'] : '';
?>
    <option
        value="<?php echo esc_attr($value); ?>"
        <?php selected($value, $current); ?>
    >
        <?php echo esc_html($label); ?>
    </option>
<?php
    endforeach;
endif;
?>
</select>