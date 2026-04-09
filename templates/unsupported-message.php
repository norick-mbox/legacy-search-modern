<script type="text/javascript">
try {
    wpcfs_assert_supported_browser();
} catch (unsupported) {
    if (unsupported === "Old IE") {
        var html = <?php echo wp_json_encode(
            '<h1>' . __('Unsupported Browser', 'legacy-search-modern') . '</h1>' .
            '<p>' . __('This browser is no longer supported by the Legacy Search Modern admin area, please upgrade to a more modern browser.', 'legacy-search-modern') . '</p>'
        ); ?>;

        document.write(html);
    }
}
</script>