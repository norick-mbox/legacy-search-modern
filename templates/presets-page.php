<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<?php include dirname(__FILE__) . '/unsupported-message.php';

?>
<h1><?php echo esc_html__( 'Configure Legacy Search Modern', 'legacy-search-modern' ); ?></h1>
<div id='wpcfs-presets-page'>
</div>

<?php
    // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$legacy_options = get_option('wp_custom_fields_search');

if (empty($legacy_options) || !is_array($legacy_options)) {
    $legacy_options = get_option('wp_custom_fields_search_options');
}
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

    if (
    is_array($legacy_options) &&
    !empty($legacy_options['presets'])
    ):
?>
    <div class="notice notice-info" style="margin:20px 0;padding:15px;">
        <p>
            <?php esc_html_e(
                    'WP Custom Fields Search settings were found. Import them into Legacy Search Modern.',
                    'legacy-search-modern'
            ); ?>
        </p>

        <p>
            <button type="button"
                class="button button-secondary"
                id="legacy-search-modern-import">
                <?php esc_html_e('Import Settings', 'legacy-search-modern'); ?>
            </button>
        </p>

        <p class="description">
            <?php esc_html_e(
                    'This will overwrite the current Legacy Search Modern settings.',
                    'legacy-search-modern'
            ); ?>
        </p>
    </div>
<?php endif; ?>