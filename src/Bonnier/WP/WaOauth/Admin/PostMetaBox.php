<?php

namespace Bonnier\WP\WaOauth\Admin;

use Bonnier\WP\WaOauth;

class PostMetaBox
{

    const UNLOCK_SETTING_KEY = 'bp_wa_oauth_unlock';
    const REQUIRED_ROLE_SETTING_KEY = 'bp_wa_oauth_required_role';

    /**
     * Registers the Bp wa oauth locked content meta box.
     *
     * @return void.
     */
    public static function register_meta_box()
    {
        add_action('do_meta_boxes', function(){
            add_meta_box('bp_wa_oauth_unlock', 'WA OAuth locked content', [__CLASS__, 'meta_box_content']);
        });

        add_action('save_post', [__CLASS__, 'save_meta_box_settings']);
    }

    public static function meta_box_content() {

        $plugin = WaOauth\instance();

        $checked = self::get_setting(self::UNLOCK_SETTING_KEY) === 'true' ? 'checked' : '';

        $fieldOutput = "<label>Unlock </label>";
        // Hidden value is required to save false when checkbox is not checked, otherwise no post value is present
        $fieldOutput .= "<input type='hidden' value='false' name='bp_wa_oauth_unlock'>";
        $fieldOutput .= "<input type='checkbox' value='true' name='bp_wa_oauth_unlock' $checked />";

        $fieldValue = self::get_setting(self::REQUIRED_ROLE_SETTING_KEY);
        $fieldOutput .= "<br><br>";
        $fieldOutput .= "<label>Required role to view content </label>";

        // Open select field
        $fieldOutput .= "<select name='bp_wa_oauth_required_role'>";

        // Get a list of user roles by system key
        $userRoles = array_map(function($option){
            return $option['system_key'];
        }, $plugin->settings->get_wa_user_roles());

        // Check if the saved value is in the array of user roles to determine if default option is selected
        $defaultSelected = in_array($fieldValue, $userRoles) ? 'selected' : '';
        // Prepend default option with no value, so the setting will default to the global option
        $fieldOutput .= "<option value='' $defaultSelected> Global default </option>";

        foreach ($userRoles as $userRole) {
            $selected = ($userRole === $fieldValue) ? 'selected' : '';
            $fieldOutput .= "<option value='" . $userRole . "' $selected >" . $userRole . "</option>";
        }
        // close Select
        $fieldOutput .= "</select>";

        print $fieldOutput;

    }

    public static function save_meta_box_settings() {

        if(isset($_POST[self::UNLOCK_SETTING_KEY])) {
            update_post_meta(
                get_the_ID(),
                self::UNLOCK_SETTING_KEY,
                sanitize_text_field($_POST[self::UNLOCK_SETTING_KEY])
            );
        }

        if(isset($_POST[self::REQUIRED_ROLE_SETTING_KEY])) {
            update_post_meta(
                get_the_ID(),
                self::REQUIRED_ROLE_SETTING_KEY,
                sanitize_text_field($_POST[self::REQUIRED_ROLE_SETTING_KEY])
            );
        }

    }

    public static function get_setting($option) {
        return get_post_meta(get_the_ID(), $option, true);
    }

    public static function post_is_unlocked($postId) {
        return get_post_meta($postId, self::UNLOCK_SETTING_KEY, true) === 'true' ? true : false;
    }

    public static function post_required_role($postId) {
        return get_post_meta($postId, self::REQUIRED_ROLE_SETTING_KEY, true);
    }




}
