<script type="text/javascript">
try {
    wpcfs_assert_supported_browser();
} catch (unsupported) {
    if (unsupported === "Old IE") {
        var html = <?php echo wp_json_encode(
            '<h1>' . __('Unsupported Browser', 'wp_custom_fields_search') . '</h1>' .
            '<p>' . __('This browser is no longer supported by the WP Custom Fields Search admin area, please upgrade to a more modern browser.', 'wp_custom_fields_search') . '</p>'
        ); ?>;

        document.write(html);
    }
}
</script>