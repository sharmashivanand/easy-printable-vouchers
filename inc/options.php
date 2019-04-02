<?php

namespace epv;

function get_setting($setting) {
    $defaults = defaults();
    $settings = wp_parse_args( get_option( 'epv', $defaults ), $defaults );
    return isset( $settings[ $setting ] ) ? $settings[ $setting ] : false;
}

function defaults(){
    $defaults = array(
        'background' => EPV_URI . 'assets/background.png',
    );
    return $defaults;
}
