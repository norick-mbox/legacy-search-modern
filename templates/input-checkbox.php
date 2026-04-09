<ul class='wpcfs-checkboxes'>
<?php 
// $query[$html_name] が存在しない場合に備えて安全に取り出す
$selected = isset( $query[ $html_name ] ) ? (array) $query[ $html_name ] : array();

$index = 0;

if ( ! empty( $options['options'] ) && is_array( $options['options'] ) ) :
    foreach ( $options['options'] as $option ) :
        $index++;
        $id = $html_name . '-' . $index;
        $value = isset( $option['value'] ) ? $option['value'] : '';
        $label = isset( $option['label'] ) ? $option['label'] : '';
        ?>
        <li>
            <input
                type="checkbox"
                name="<?php echo htmlspecialchars( $html_name ); ?>[]"
                value="<?php echo htmlspecialchars( $value ); ?>"
                <?php if ( in_array( $value, $selected, true ) ) : ?> checked="checked"<?php endif; ?>
                id="<?php echo htmlspecialchars( $id ); ?>"
            />
            <label for="<?php echo htmlspecialchars( $id ); ?>">
                <?php echo htmlspecialchars( $label ); ?>
            </label>
        </li>
    <?php
    endforeach;
endif;
?>
</ul>