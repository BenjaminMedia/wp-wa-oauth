<?php
/**
 * Plugin Name: Bonnier WhiteAlbum OAuth
 * Version: 1.1.0
 * Plugin URI: https://github.com/BenjaminMedia/wp-wa-oauth
 * Description: This plugin allows you to integrate your site with the whitealbum oauth user api
 * Author: Bonnier - Alf Henderson
 * License: GPL v3
 */

namespace Bonnier\WP\WaOauth;

use Bonnier\WP\WaOauth\Admin\PostMetaBox;
use Bonnier\WP\WaOauth\Assets\Scripts;
use Bonnier\WP\WaOauth\Http\Routes\OauthLoginRoute;
use Bonnier\WP\WaOauth\Http\Routes\UserUpdateCallbackRoute;
use Bonnier\WP\WaOauth\Models\User;
use Bonnier\WP\WaOauth\Settings\SettingsPage;

// Do not access this file directly
if (!defined('ABSPATH')) {
    exit;
}

// Handle autoload so we can use namespaces
spl_autoload_register(function ($className) {
    if (strpos($className, __NAMESPACE__) !== false) {
        $className = str_replace("\\", DIRECTORY_SEPARATOR, $className);
        require_once(__DIR__ . DIRECTORY_SEPARATOR . Plugin::CLASS_DIR . DIRECTORY_SEPARATOR . $className . '.php');
    }
});

// Load plugin api
require_once (__DIR__ . '/'.Plugin::CLASS_DIR.'/api.php');

class Plugin
{
    /**
     * Text domain for translators
     */
    const TEXT_DOMAIN = 'bp-wa-oauth';

    const CLASS_DIR = 'src';

    /**
     * @var object Instance of this class.
     */
    private static $instance;

    public $settings;

    private $loginRoute;

    /**
     * @var string Filename of this class.
     */
    public $file;

    /**
     * @var string Basename of this class.
     */
    public $basename;

    /**
     * @var string Plugins directory for this plugin.
     */
    public $plugin_dir;

    /**
     * @var string Plugins url for this plugin.
     */
    public $plugin_url;

    /**
     * Do not load this more than once.
     */
    private function __construct()
    {
        // Set plugin file variables
        $this->file = __FILE__;
        $this->basename = plugin_basename($this->file);
        $this->plugin_dir = plugin_dir_path($this->file);
        $this->plugin_url = plugin_dir_url($this->file);

        // Load textdomain
        load_plugin_textdomain(self::TEXT_DOMAIN, false, dirname($this->basename) . '/languages');

        $this->settings = new SettingsPage();
        $this->loginRoute = new OauthLoginRoute($this->settings);
        new UserUpdateCallbackRoute($this->settings);
    }

    private function boostrap() {
        Scripts::bootstrap();
        PostMetaBox::register_meta_box();
    }

    /**
     * Returns the instance of this class.
     */
    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self;
            global $bp_wa_oauth;
            $bp_wa_oauth = self::$instance;
            self::$instance->boostrap();

            /**
             * Run after the plugin has been loaded.
             */
            do_action('bp_wa_oauth_loaded');
        }

        return self::$instance;
    }

    public function is_authenticated($postId = null) {
        return $this->loginRoute->is_authenticated($postId = null);
    }

    public function get_user() {
        $waUser = $this->loginRoute->get_wa_user();
        if($this->settings->get_create_local_user($this->settings->get_current_locale())) {
            return User::get_local_user($waUser);
        }
        return $waUser;
    }

    public function is_locked($postId = null) {

        if(!$postId) {
            $postId = get_the_ID();
        }

        $currentLang = $this->settings->get_current_language();

        $locale = $currentLang ? $currentLang->locale: null;

        $globalLock = $this->settings->get_global_enable($locale);
        $postUnlocked = PostMetaBox::post_is_unlocked($postId);

        return $postUnlocked ? false : $globalLock;
    }


}

/**
 * @return Plugin $instance returns an instance of the plugin
 */
function instance()
{
    return Plugin::instance();
}

add_action('plugins_loaded', __NAMESPACE__ . '\instance', 0);
