<?php
    $placeholder = isset($options['label']) ? $options['label'] : '';
    $value = isset($query[$html_name]) ? $query[$html_name] : '';
?>

<input
    type="text"
    name="<?php echo esc_attr($html_name); ?>"
    id="<?php echo esc_attr($html_id); ?>"
    value="<?php echo esc_attr($value); ?>"
    placeholder="<?php echo esc_attr($placeholder); ?>"
/>