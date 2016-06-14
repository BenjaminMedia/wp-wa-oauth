<?php

namespace Bonnier\WP\WaOauth\Settings;

use Exception;
use Bonnier\WP\WaOauth\Services\ServiceOAuth;
use PLL_Language;

class SettingsPage
{
    const SETTINGS_KEY = 'bp_wa_oauth_settings';
    const SETTINGS_GROUP = 'bp_wa_oauth_settings_group';
    const SETTINGS_SECTION = 'bp_wa_oauth_settings_section';
    const SETTINGS_PAGE = 'bp_wa_oauth_settings_page';
    const API_ENDPOINT_FALLBACK = 'http://woman.dk/';
    const NOTICE_PREFIX = 'Bonnier Wa Oauth:';
    const ROLES_PREFIX = 'bp_wa_';
    const ROLE_CAPABILITIES_KEY = 'bp_wa_roles_capabilities';

    private $settingsFields = [
        'api_key' => [
            'type' => 'text',
            'name' => 'Api Key',
        ],
        'api_secret' => [
            'type' => 'text',
            'name' => 'Api Secret',
        ],
        'api_endpoint' => [
            'type' => 'text',
            'name' => 'Api Endpoint',
        ],
        'global_enable' => [
            'type' => 'checkbox',
            'name' => 'Global Enable',
        ],
        'user_role' => [
            'type' => 'select',
            'name' => 'User Role Required',
            'options_callback' => 'get_wa_user_roles'
        ],
        'create_local_user' => [
            'type' => 'checkbox',
            'name' => 'Create local user',
        ],
        'auto_login_local_user' => [
            'type' => 'checkbox',
            'name' => 'Auto login local user',
        ],
    ];

    /**
     * Holds the values to be used in the fields callbacks
     */
    private $settingsValues;

    /**
     * Start up
     */
    public function __construct()
    {
        $this->settingsValues = get_option(self::SETTINGS_KEY);
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    function print_error($error)
    {
        $out = "<div class='error settings-error notice is-dismissible'>";
        $out .= "<strong>" . self::NOTICE_PREFIX . "</strong><p>$error</p>";
        $out .= "</div>";
        print $out;
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin',
            'Bonnier WA OAuth',
            'manage_options',
            self::SETTINGS_PAGE,
            array($this, 'create_admin_page')
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property

        ?>
        <div class="wrap">
            <form method="post" action="options.php">
                <?php
                // This prints out all hidden setting fields
                settings_fields(self::SETTINGS_GROUP);
                do_settings_sections(self::SETTINGS_PAGE);
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function register_settings()
    {
        if ($this->languages_is_enabled()) {
            $this->enable_language_fields();
        }

        register_setting(
            self::SETTINGS_GROUP, // Option group
            self::SETTINGS_KEY, // Option name
            array($this, 'sanitize') // Sanitize
        );

        add_settings_section(
            self::SETTINGS_SECTION, // ID
            'Bonnier WA OAuth Settings', // Title
            array($this, 'print_section_info'), // Callback
            self::SETTINGS_PAGE // Page
        );

        foreach ($this->settingsFields as $settingsKey => $settingField) {
            add_settings_field(
                $settingsKey, // ID
                $settingField['name'], // Title
                array($this, $settingsKey), // Callback
                self::SETTINGS_PAGE, // Page
                self::SETTINGS_SECTION // Section
            );
        }
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     * @return array
     */
    public function sanitize($input)
    {
        $sanitizedInput = [];

        foreach ($this->settingsFields as $fieldKey => $settingsField) {
            if (isset($input[$fieldKey])) {
                if ($settingsField['type'] === 'checkbox') {
                    $sanitizedInput[$fieldKey] = absint($input[$fieldKey]);
                }
                if ($settingsField['type'] === 'text' || $settingsField['type'] === 'select') {
                    $sanitizedInput[$fieldKey] = sanitize_text_field($input[$fieldKey]);
                }
            }
        }

        return $sanitizedInput;
    }

    /**
     * Print the Section text
     */
    public function print_section_info()
    {
        print 'Enter your settings below:';
    }

    /**
     * Catch callbacks for creating setting fields
     * @param string $function
     * @param array $arguments
     * @return bool
     */
    public function __call($function, $arguments)
    {
        if (!isset($this->settingsFields[$function])) {
            return false;
        }

        $field = $this->settingsFields[$function];
        $this->create_settings_field($field, $function);

    }

    public function get_setting_value($settingKey, $locale = null)
    {
        if(!$this->settingsValues) {
            $this->settingsValues = get_option(self::SETTINGS_KEY);
        }

        if ($locale) {
            $settingKey = $locale . '_' . $settingKey;
        }

        if (isset($this->settingsValues[$settingKey]) && !empty($this->settingsValues[$settingKey])) {
            return $this->settingsValues[$settingKey];
        }
        return false;
    }

    public function get_api_endpoint($locale = null)
    {
        return $this->get_setting_value('api_endpoint', $locale) ?: self::API_ENDPOINT_FALLBACK;
    }

    public function get_api_user($locale = null)
    {
        return $this->get_setting_value('api_key', $locale) ?: '';
    }

    public function get_api_secret($locale = null)
    {
        return $this->get_setting_value('api_secret', $locale) ?: '';
    }

    public function get_required_user_role($locale = null)
    {
        return $this->get_setting_value('api_secret', $locale) ?: '';
    }

    public function get_global_enable($locale = null)
    {
        return $this->get_setting_value('global_enable', $locale) ?: '';
    }

    public function get_create_local_user($locale = null)
    {
        return $this->get_setting_value('create_local_user', $locale) ?: '';
    }

    public function get_auto_login_local_user($locale = null)
    {
        return $this->get_setting_value('auto_login_local_user', $locale) ?: '';
    }

    private function enable_language_fields()
    {
        $languageEnabledFields = [];

        foreach ($this->get_languages() as $language) {
            foreach ($this->settingsFields as $fieldKey => $settingsField) {

                $localeFieldKey = $language->locale . '_' . $fieldKey;
                $languageEnabledFields[$localeFieldKey] = $settingsField;
                $languageEnabledFields[$localeFieldKey]['name'] .= ' ' . $language->locale;
                $languageEnabledFields[$localeFieldKey]['locale'] = $language->locale;

            }
        }

        $this->settingsFields = $languageEnabledFields;

    }

    public function languages_is_enabled()
    {
        return function_exists('Pll') && PLL()->model->get_languages_list();
    }

    public function get_languages()
    {
        if ($this->languages_is_enabled()) {
            return PLL()->model->get_languages_list();
        }
        return false;
    }

    /**
     * Get the current language by looking at the current HTTP_HOST
     *
     * @return null|PLL_Language
     */
    public function get_current_language()
    {
        if ($this->languages_is_enabled()) {
            return PLL()->model->get_language(pll_current_language());
        }
        return null;
    }

    public function get_current_locale() {
        $currentLang = $this->get_current_language();
        return $currentLang ? $currentLang->locale : null;
    }

    private function get_select_field_options($field)
    {
        if (isset($field['options_callback'])) {
            $options = $this->{$field['options_callback']}($field['locale']);
            if ($options) {
                return $options;
            }
        }

        return [];

    }

    public function get_wa_user_roles($locale = null)
    {
        $service = new ServiceOAuth(
            $this->get_api_user($locale),
            $this->get_api_secret($locale),
            $this->get_api_endpoint($locale)
        );

        try {
            $userRoles = $service->getUserRoleList();
            $this->create_wp_user_roles($userRoles);
            return $userRoles;
        } catch (Exception $e) {
            $this->print_error('Failed fetching user roles: ' . $e->getMessage());
            return false;
        }
    }

    private function create_settings_field($field, $fieldKey)
    {
        $fieldName = self::SETTINGS_KEY . "[$fieldKey]";
        $fieldOutput = false;

        if ($field['type'] === 'text') {
            $fieldValue = isset($this->settingsValues[$fieldKey]) ? esc_attr($this->settingsValues[$fieldKey]) : '';
            $fieldOutput = "<input type='text' name='$fieldName' value='$fieldValue' class='regular-text' />";
        }
        if ($field['type'] === 'checkbox') {
            $checked = isset($this->settingsValues[$fieldKey]) && $this->settingsValues[$fieldKey] ? 'checked' : '';
            $fieldOutput = "<input type='hidden' value='0' name='$fieldName'>";
            $fieldOutput .= "<input type='checkbox' value='1' name='$fieldName' $checked />";
        }
        if ($field['type'] === 'select') {
            $fieldValue = isset($this->settingsValues[$fieldKey]) ? $this->settingsValues[$fieldKey] : '';
            $fieldOutput = "<select name='$fieldName'>";
            $options = $this->get_select_field_options($field);
            foreach ($options as $option) {
                $selected = ($option['system_key'] === $fieldValue) ? 'selected' : '';
                $fieldOutput .= "<option value='" . $option['system_key'] . "' $selected >" . $option['system_key'] . "</option>";
            }
            $fieldOutput .= "</select>";
        }

        if ($fieldOutput) {
            print $fieldOutput;
        }
    }

    private function create_wp_user_roles($roles) {

        if (is_array($roles)) {
            foreach ($roles as $role) {

                $roleKey = self::ROLES_PREFIX . $role['system_key'];

                $defaultCapabilities = [
                    'read' => true
                ];

                $capabilities = apply_filters($roleKey . '_capabilities', $defaultCapabilities);
                $existingRole = get_role($roleKey);

                if($existingRole && count(array_diff_assoc($existingRole->capabilities, $capabilities)) > 0 ) {
                    remove_role($roleKey);
                }
                add_role($roleKey,
                    'Bonnier WhiteAlbum '.$role['name'],
                    $capabilities
                );
            }
        }
    }

}