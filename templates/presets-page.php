<?php include(dirname(__FILE__).'/unsupported-message.php');

?>
<h1><?php echo __("Configure Legacy Search Modern","legacy-search-modern")?></h1>
<div id='wpcfs-presets-page'>
</div>

<?php
    $legacy_options = get_option('wp_custom_fields_search');

    if (!empty($legacy_options)):
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