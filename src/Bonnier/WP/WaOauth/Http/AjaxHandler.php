<?php


namespace Bonnier\WP\WaOauth\Http;

use Bonnier\WP\WaOauth\Models\User;

class AjaxHandler
{
    public static function register()
    {
        add_action('wp_ajax_wp_wa_oauth_logout', [__CLASS__, 'logout']);
        add_action('wp_ajax_nopriv_wp_wa_oauth_logout', [__CLASS__, 'logout']);
    }

    public static function logout()
    {
        $user = wp_get_current_user();
        if($user) {
            foreach($user->roles as $role) {
                if(substr($role, 0, 6) === 'bp_wa_') {
                    wp_logout();
                    wp_send_json(['status' => 'ok', 'refresh' => true]);
                    die();
                }
            }
        }
        wp_send_json(['status' => 'ok', 'refresh' => false]);
    }
}
