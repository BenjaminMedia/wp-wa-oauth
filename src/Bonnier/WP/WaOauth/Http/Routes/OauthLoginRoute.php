<?php

namespace Bonnier\WP\WaOauth\Http\Routes;

use Bonnier\WP\WaOauth\Http\Exceptions\HttpException;
use Exception;
use Bonnier\WP\WaOauth\Services\ServiceOAuth;
use Bonnier\WP\WaOauth\Settings\SettingsPage;
use WP_REST_Request;
use WP_REST_Response;

class OauthLoginRoute
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
     * The login route.
     */
    const LOGIN_ROUTE = '/oauth/login';

    /**
     * The get user route.
     */
    const GET_USER_ROUTE = '/oauth/user';

    /**
     * The access token cookie lifetime.
     */
    const COOKIE_LIFETIME_HOURS = 24;

    /**
     * The access token cookie key.
     */
    const COOKIE_KEY = 'wa_token';

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
            register_rest_route($this->get_route_namespace(), self::LOGIN_ROUTE, [
                'methods' => 'GET, POST',
                'callback' => [$this, 'login'],
            ]);
        });
    }

    /**
     * The function that handles the user login request
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function login(WP_REST_Request $request)
    {
        $this->service = $this->get_oauth_service();

        // Persist admin destination
        //$this->setAdminDestination();

        // Get user from admin service
        try {
            $waUser = $this->get_wa_user($request);
        } catch (HttpException $e) {
            return new WP_REST_Response(['error' => $e->getMessage()], $e->getCode());
        }


        // If the user is not logged in, we redirect to the login screen.
        if (!$waUser) {
            $this->trigger_login_flow();
        }

        return new WP_REST_Response($waUser, 200);

        // Get the matching local user
        //$localUser = $this->getLocalUser($waUser);

        // Set the jwt token from user
        //$this->auth->setUser($localUser);

        // Check if admin destination has been set
        //$adminRedirect = $this->getAdminDestination();

        //if($adminRedirect) {

        // redirect to admin destination with token
        //  return redirect($adminRedirect);

        //}


    }


    /**
     * Triggers the login flow by redirecting the user to the login Url
     *
     */
    private function trigger_login_flow()
    {
        $currentLang = $this->settings->get_current_language();

        $this->redirect(
            $this->service->getLoginUrl(
                $this->get_redirect_uri(),
                $this->settings->get_required_user_role($currentLang->locale))
        );

    }

    /**
     *
     *
     * @param WP_REST_Request $request
     * @return mixed
     * @throws Exception|HttpException
     */
    private function get_wa_user(WP_REST_Request $request)
    {
        $redirectUri = $this->get_redirect_uri();

        if ($accessToken = $this->get_access_token()) {

            $this->service->setAccessToken($accessToken);

        } elseif ($grantToken = $request->get_param('code')) {

            $this->service->setGrantToken($redirectUri, $grantToken);
            $this->persist_access_token($this->service->getAccessToken());
        }

        return $this->service->getUser();

    }

    /**
     * Redirect the user to provided path
     *
     * @param $to
     */
    private function redirect($to)
    {
        header("Location: " . $to);
        exit();
    }

    /**
     * Returns the route namespace
     *
     * @return string
     */
    private function get_route_namespace()
    {
        return self::PLUGIN_PREFIX . DIRECTORY_SEPARATOR . self::VERSION;
    }

    /**
     * Returns the persisted access token or false
     *
     * @return string|bool
     */
    private function get_access_token()
    {
        return isset($_COOKIE[self::COOKIE_KEY]) ? $_COOKIE[self::COOKIE_KEY] : false;
    }

    /**
     * Persists the Access token for later use
     *
     * @param $token
     */
    private function persist_access_token($token)
    {
        setcookie(self::COOKIE_KEY, $token, $this->get_cookie_lifetime(), '/');
    }

    /**
     * Gets the cookie lifetime
     *
     * @return int
     */
    private function get_cookie_lifetime()
    {
        return time() + (self::COOKIE_LIFETIME_HOURS * 60 * 60);
    }

    /**
     * Returns the currently used HTTP protocol
     *
     * @return string
     */
    private function get_http_protocol()
    {
        return strpos('HTTP', getenv('SERVER_PROTOCOL')) === false ? 'http://' : 'https://';
    }

    /**
     * Returns the host including the HTTP protocol
     *
     * @return string
     */
    private function get_host()
    {
        return $this->get_http_protocol() . getenv('HTTP_HOST');
    }

    /**
     * Gets the redirect uri that matches the login route
     *
     * @return string
     */
    private function get_redirect_uri()
    {
        return $this->get_host()
        . DIRECTORY_SEPARATOR
        . self::BASE_PREFIX
        . DIRECTORY_SEPARATOR
        . $this->get_route_namespace()
        . self::LOGIN_ROUTE;
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

        $locale = null;

        if ($currentLang = $this->settings->get_current_language()) {
            $locale = $currentLang->locale;
        }

        return new ServiceOAuth(
            $this->settings->get_api_user($locale),
            $this->settings->get_api_secret($locale),
            $this->settings->get_api_endpoint($locale)
        );
    }

}