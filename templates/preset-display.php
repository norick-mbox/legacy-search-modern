<?php
if (!defined('ABSPATH')) {
    exit;
} ?>


<div class="wpcfs-search-preset wpcfs-search-preset-<?php echo esc_attr($id); ?>">
<?php
        WPCFSSearchForm::show_form($preset,"preset-$id");
?>
</div>
