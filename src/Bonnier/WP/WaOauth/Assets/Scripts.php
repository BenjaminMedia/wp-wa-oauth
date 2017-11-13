<?php

namespace Bonnier\WP\WaOauth\Assets;

use Bonnier\WP\WaOauth;

class Scripts
{
    /** @var WaOauth\Settings\SettingsPage */
    private static $settings;

    public static function bootstrap(WaOauth\Settings\SettingsPage $settings)
    {
        self::$settings = $settings;

        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_login_script']);
    }

    public static function enqueue_login_script()
    {
        $plugin = WaOauth\instance();

        $script_src = $plugin->plugin_url . 'js/bp-wa-oauth-login.js';

        wp_enqueue_script('bp-wa-oauth-login', $script_src);
        wp_localize_script('bp-wa-oauth-login', 'settings', ['ajaxurl' => admin_url('admin-ajax.php'), 'api_endpoint' => self::$settings->get_api_endpoint()]);
    }
}
