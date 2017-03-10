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

    public static function get_user_id_from_email($email) {
        global $wpdb;
        return $wpdb->get_var(
            $wpdb->prepare("SELECT ID FROM wp_users WHERE user_email='%s'", $email)
        );
    }

    public static function update_user_nicename($userId, $nicename) {
        global $wpdb;
        return $wpdb->update('wp_users', ['user_nicename' => $nicename], ['ID' => $userId], ['%s'], ['%d']);
    }

    public static function update_user_login($userObject, $new_login) {
        global $wpdb;
        return $wpdb->update('wp_users', ['user_login' => $new_login], ['ID' => $userObject->ID], ['%s'], ['%d']);
    }

    public static function get_access_token($userId) {
        return get_user_meta($userId, self::ACCESS_TOKEN_META_KEY, true);
    }

    public static function set_access_token($userId, $value) {
        return update_user_meta($userId, self::ACCESS_TOKEN_META_KEY, $value);
    }

    public static function create_local_user($waUser, $accessToken) {

        $localUser = static::get_local_user($waUser);

        $localUser = self::set_user_props($localUser, $waUser);

        $userId = wp_insert_user($localUser);

        // We have to update the user nicename because wp appends -2 when we call wp_insert_user
        self::update_user_nicename($userId, $waUser->username);

        self::set_access_token($userId, $accessToken);

        update_user_meta($userId, 'wa_user_id', $waUser->id);

        self::update_local_user($userId, $waUser);
    }

    /**
     * @param $waUser
     *
     * @return WP_User|null
     */
    public static function get_local_user($waUser) {
        $localUser = new WP_User(self::get_local_user_id($waUser->id));
        if(!$localUser->exists()) { // check if user can be found by email
            $localUser = new WP_User(self::get_user_id_from_email($waUser->email));
        }
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

            // if a user's login is not already found in the database, we'll update the current one to the one in the $localUser object.
            $updated = wp_update_user($localUser) && self::update_user_login($localUser, $localUser->user_login);

            return ! is_wp_error($updated);
        }
        return false;
    }

    private static function set_user_props(WP_User $localUser, $waUser) {
        
        $localUser->user_login = sanitize_user($waUser->username);
        $localUser->first_name = $waUser->first_name;
        $localUser->last_name = $waUser->last_name;
        $localUser->user_nicename = $waUser->username;
        $localUser->display_name = $waUser->username;
        $localUser->nickname = $waUser->username;
        $localUser->user_url = $waUser->url;
        $localUser->user_email = $waUser->email;

        // Password is required when creating a new user
        if(! $localUser->exists()) {
            $localUser->user_pass = md5($waUser->username . time());
        }

        $localUser = self::set_user_roles($localUser, $waUser->roles);

        /*
         * this filter is for if you want to insert data into the description field,
         * and/or other fields which has not been set by the WA user above.
         * If these fields are not set, WP will automatically set them to an empty string, every time the user logs in.
         */
        $localUser = apply_filters(self::ON_USER_UPDATE_HOOK, [
            'wp' => $localUser,
            'wa' => $waUser
        ]);

        if ( $localUser instanceof WP_User ) {
            return $localUser;
        }
        else{
            return $localUser['wp'];
        }
    }

    private static function set_user_roles($localUser, $roles)
    {
        foreach ($roles as $role) {
            $localUser->set_role(SettingsPage::ROLES_PREFIX . $role);
        }
        return $localUser;
    }
}
