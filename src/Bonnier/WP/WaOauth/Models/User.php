<?php

namespace Bonnier\WP\WaOauth\Models;

use Bonnier\WP\WaOauth\Settings\SettingsPage;
use WP_User;

class User
{
    const ACCESS_TOKEN_META_KEY = 'bp_wa_oauth_access_token';
    const ON_USER_UPDATE_HOOK = 'bp_wa_oauth_on_user_update';

    public static function get_local_user_id($waId) {
        global $wpdb;
        return $wpdb->get_var(
            $wpdb->prepare("SELECT user_id FROM wp_usermeta WHERE meta_key=%s AND meta_value=%d", 'wa_user_id', $waId)
        );
    }

    public static function get_access_token($userId) {
        return get_user_meta($userId, self::ACCESS_TOKEN_META_KEY, true);
    }

    public static function set_access_token($userId, $value) {
        return update_user_meta($userId, self::ACCESS_TOKEN_META_KEY, $value);
    }

    public static function create_local_user($waUser, $accessToken) {

        $localUser = new WP_User(self::get_local_user_id($waUser->id));

        $localUser = self::set_user_props($localUser, $waUser);

        $userId = wp_insert_user($localUser);

        $localUser = apply_filters(self::ON_USER_UPDATE_HOOK, $localUser);

        wp_update_user($localUser);

        self::set_access_token($userId, $accessToken);

        update_user_meta($userId, 'wa_user_id', $waUser->id);
    }

    public static function get_local_user($waUser) {
        $localUser = new WP_User(self::get_local_user_id($waUser->id));
        return $localUser ?: null;
    }

    public static function wp_login_user($user) {
        if(isset($user->ID)) {
            wp_set_current_user($user->ID, $user->user_login);
            wp_set_auth_cookie($user->ID);
            do_action( 'wp_login', $user->user_login );
        }
        return false;
    }

    public static function update_local_user($localUserId, $waUser)
    {
        $localUser = new WP_User($localUserId);

        if($localUser->exists()) {

            $localUser = self::set_user_props($localUser, $waUser);

            $localUser = apply_filters(self::ON_USER_UPDATE_HOOK, $localUser);

            $updated = wp_update_user($localUser);

            return ! is_wp_error($updated);
        }
        return false;
    }

    private static function set_user_props($localUser, $waUser) {
        
        $localUser->user_login = $waUser->username;
        $localUser->first_name = $waUser->first_name;
        $localUser->last_name = $waUser->last_name;
        $localUser->user_url = $waUser->url;
        $localUser->user_email = $waUser->email;

        // Password is required when creating a new user
        if(! $localUser->exists()) {
            $localUser->user_pass = md5($waUser->username . time());
        }

        $localUser = self::set_user_roles($localUser, $waUser->roles);

        return $localUser;
    }

    private static function set_user_roles($localUser, $roles)
    {
        foreach ($roles as $role) {
            $localUser->set_role(SettingsPage::ROLES_PREFIX . $role);
        }
        return $localUser;
    }
}