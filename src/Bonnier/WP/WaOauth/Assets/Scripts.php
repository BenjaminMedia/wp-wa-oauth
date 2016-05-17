<?php

namespace Bonnier\WP\WaOauth\Assets;

use Bonnier\WP\WaOauth;

class Scripts
{
    public static function bootstrap()
    {
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_login_script']);
    }

    public static function enqueue_login_script()
    {
        $plugin = WaOauth\instance();

        $script_src = $plugin->plugin_url . 'js/bp-wa-oauth-login.js';

        wp_enqueue_script('bp-wa-oauth-login', $script_src);
    }
}