<?php

namespace Bonnier\WP\WaOauth\Http\Routes;

use Bonnier\WP\WaOauth\Http\Exceptions\HttpException;
use Bonnier\WP\WaOauth\Models\User;
use Bonnier\WP\WaOauth\Services\ServiceOAuth;
use Bonnier\WP\WaOauth\Settings\SettingsPage;
use WP_REST_Request;
use WP_REST_Response;

class UserUpdateCallbackRoute
{
    const BASE_PREFIX = 'wp-json';

    /**
     * The namespace prefix.
     */
    const PLUGIN_PREFIX = 'bp-wa-oauth';

    /**
     * The namespace version.
     */
    const VERSION = 'v1';

    /**
     * The update user callback route
     */
    const USER_CALLBACK_ROUTE = '/oauth/callback';

    /**
     * The access token cookie lifetime.
     */
    const ACCESS_TOKEN_LIFETIME_HOURS = 24;

    /* @var SettingsPage $settings */
    private $settings;

    /* @var ServiceOAuth $service */
    private $service;

    /**
     * OauthLoginRoute constructor.
     * @param SettingsPage $settings
     */
    public function __construct(SettingsPage $settings)
    {
        $this->settings = $settings;

        add_action('rest_api_init', function () {
            register_rest_route($this->get_route_namespace(), self::USER_CALLBACK_ROUTE, [
                'methods' => 'POST',
                'callback' => [$this, 'update_user_callback'],
            ]);
        });
    }

    /**
     * The function that handles the user login request
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function update_user_callback(WP_REST_Request $request)
    {
        $action = $request->get_param('action');
        $waUserId = $request->get_param('payload');

        if($action === 'user_updated' && $waUserId) {
            $localUserId = User::get_local_user_id($waUserId);
            $accessToken = User::get_access_token($localUserId);

            if($accessToken && !empty($accessToken)) {
                $this->update_user($localUserId, $accessToken);
            }

        }

        return new WP_REST_Response('ok', 200);
    }

    private function update_user($localUserId, $accessToken) {

        $service = $this->get_oauth_service();
        $service->setAccessToken($accessToken);

        try {
            $updatedUser = $service->getUser();
        } catch (HttpException $e) {
            return false;
        }

        if($updatedUser) {

            return User::update_local_user($localUserId, $updatedUser);
        }

        return false;
    }

    /**
     * Returns the route namespace
     *
     * @return string
     */
    private function get_route_namespace()
    {
        return self::PLUGIN_PREFIX . '/' . self::VERSION;
    }


    /**
     * Returns an instance of ServiceOauth
     *
     * @return ServiceOAuth
     */
    private function get_oauth_service()
    {
        if ($this->service) {
            return $this->service;
        }

        $locale = $this->settings->get_current_locale();

        return new ServiceOAuth(
            $this->settings->get_api_user($locale),
            $this->settings->get_api_secret($locale),
            $this->settings->get_api_endpoint($locale)
        );
    }

}