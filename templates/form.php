<?php
if (!defined('ABSPATH')) {
    exit;
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Widget wrapper HTML is provided by WordPress core.
echo $args['before_widget'];

if (
    !empty($settings) &&
    !empty($settings['show_title'])
) {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Widget title wrapper HTML is provided by WordPress core.
    echo $args['before_title'];

    echo esc_html($settings['form_title'] ?? '');

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Widget title wrapper HTML is provided by WordPress core.
    echo $args['after_title'];
}
?>

<?php do_action('wpcfs-before-form')?>
<form method="<?php echo esc_attr($method); ?>"
      action="<?php echo esc_url($results_page); ?>"
      class="wpcfs-search-form"
      id="<?php echo esc_attr($form_id); ?>">
<?php 
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
foreach ((is_array($components) ? $components : array()) as $config) {

    $html_name = isset($config['html_name']) ? $config['html_name'] : '';
    $label = isset($config['label']) ? $config['label'] : '';
    $html_id = isset($config['html_id']) ? $config['html_id'] : '';
    $className = (
        isset($config['class']) && is_object($config['class'])
    )
    ? $config['class']->get_name()
    : '';
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
?>
<div class="wpcfs-input-wrapper wpcfs-input-input <?php
                                                      echo esc_attr(
                                                              sanitize_html_class($html_name) . ' ' .
                                                              sanitize_html_class(strtolower($label)) . ' ' .
                                                              sanitize_html_class(strtolower($className))
                                                      );
                                                      ?>">
    <label for="<?php echo esc_attr($html_id); ?>" class="wpcfs-label">
        <?php echo esc_html($label); ?>
    </label>
    <div class="wpcfs-input">
        <?php
            if (isset($config['class']) && is_object($config['class'])) {
                    $config['class']->render($config, $query);
                }
        ?>
    </div>
</div>
<?php endforeach; ?>

<div class='wpcfs-input-wrapper wpcfs-input-submit'>
    <input type="submit" value="<?php echo esc_attr(__('Search', 'legacy-search-modern')); ?>">
</div>

<?php echo wp_kses_post($hidden); ?>

</form>
<?php do_action('wpcfs-after-form')?>
<?php
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo $args['after_widget']; ?>