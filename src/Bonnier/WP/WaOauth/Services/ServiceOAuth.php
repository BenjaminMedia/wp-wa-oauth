<?php

namespace Bonnier\WP\WaOauth\Services;

use Bonnier\WP\WaOauth\Http\Client;

class ServiceOAuth extends Client
{

    const SERVICE_URL = 'https://bonnier-admin.herokuapp.com/';
    /** @var null|string Overrides self::SERVICE_URL */
    private $serviceEndpoint = null;

    private $accessToken, $appId, $appSecret, $user, $userRoleList;

    public function __construct($appId, $appSecret, $serviceEndpoint = null)
    {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->serviceEndpoint = $serviceEndpoint;

        parent::__construct(['base_uri' => $this->getServiceUrl()]);
    }

    /**
     * @return string
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * @param string $appId
     */
    public function setAppId($appId)
    {
        $this->appId = $appId;
    }

    /**
     * @return string
     */
    public function getAppSecret()
    {
        return $this->appSecret;
    }

    /**
     * @param string $appSecret
     */
    public function setAppSecret($appSecret)
    {
        $this->appSecret = $appSecret;
    }

    /**
     * Get the currently active access_token
     *
     * @return mixed
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * Set the access token
     *
     * @param $token
     */
    public function setAccessToken($token)
    {
        $this->accessToken = $token;
    }

    /**
     * Sets grant token and thereby provides a valid access_token
     *
     * @param $redirectUrl
     * @param $code
     * @throws Exception
     */
    public function setGrantToken($redirectUrl, $code)
    {
        $data = [
            'client_id' => $this->appId,
            'client_secret' => $this->appSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUrl
        ];

        $response = $this->post('/oauth/token', ['body' => $data]);
        $response = json_decode($response->getBody(), true);

        if (isset($response['error'])) {
            throw new Exception($response['error_description']);
        }

        if (!isset($response['access_token'])) {
            throw new Exception('Failed to get valid access_token');
        }

        $this->accessToken = $response['access_token'];
    }

    /**
     * Get the currently signed in user.
     *
     * @return mixed
     * @throws Exception
     */
    public function getUser()
    {
        if ($this->user !== null) {
            return $this->user;
        }

        if ($this->accessToken) {

            $response = $this->get('oauth/user', ['headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken
            ]]);

            if ($response->getStatusCode() == 200) {
                $this->user = json_decode($response->getBody());
            }
        }

        return $this->user;
    }

    /**
     * Get login url
     *
     * @param string $redirectUri
     * @param null|string $userRole the user role required by the user logging in
     * @return string
     */
    public function getLoginUrl($redirectUri = '', $userRole = null)
    {
        $params = [
            'client_id' => $this->appId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code'
        ];

        if ($userRole) {
            $params['accessible_for'] = $userRole;
        }

        return $this->getUrl('oauth/authorize', $params);
    }

    /**
     * Generates url with parameters
     *
     * @param $url
     * @param array $params
     * @return string
     */
    protected function getUrl($url, $params = array())
    {
        $url = rtrim($this->getServiceUrl(), '/') . '/' . $url;

        if ($params) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }


    /**
     * Preppends api/ to the sub url if needed by the set service url
     *
     * @param $url
     * @return string $url the appropriate sub url
     */
    private function getSubUrl($url)
    {
        if ($this->serviceEndpoint !== self::SERVICE_URL) {
            $url = 'api/v1/' . $url;
        }
        return $url;
    }

    /**
     * Get service url
     *
     * @return string
     */
    public function getServiceUrl()
    {
        if (is_null($this->serviceEndpoint)) {
            return self::SERVICE_URL;
        }
        return $this->serviceEndpoint;
    }

    public function getUserRoleList()
    {
        if ($this->userRoleList !== null) {
            return $this->userRoleList;
        }

        $response = $this->get('api/user_roles');

        if ($response->getStatusCode() == 200) {
            $this->userRoleList = json_decode($response->getBody(), true);
        }

        return $this->userRoleList;
    }

}