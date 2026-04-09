<?php

    function wpcfs_strip_hash_keys($data){
        $expressions = array('"\$\$hashKey":"[^"]*"','"unsaved": *(true|false)');

        foreach($expressions as $expression){
            $formats = array("/$expression,/","/,$expression/","/{".$expression."}/");
            $data = preg_replace($formats,'',$data);
        }
        return $data;
    }

    function wpcfs_escape_string( $string ) {
    global $wpdb;

    // 念のため配列／オブジェクトも文字列化
    if ( is_array( $string ) || is_object( $string ) ) {
        $string = wp_json_encode( $string );
    }

    // $wpdb が利用可能なら esc_sql() を使用
    if ( isset( $wpdb ) && $wpdb instanceof wpdb ) {
        return esc_sql( (string) $string );
    }

    // フォールバック（WordPress コンテキスト外など）
    return addslashes( (string) $string );
}
