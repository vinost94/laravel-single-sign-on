<?php

namespace Vinothst94\LaravelSingleSignOn;

use Illuminate\Support\Facades\Cookie;
use Vinothst94\LaravelSingleSignOn\Exceptions\MissingConfigurationException;
use Illuminate\Support\Str;
use GuzzleHttp;
use Vinothst94\LaravelSingleSignOn\Interfaces\SSOClientInterface;

/**
 * Class SSOClient. This class is only a skeleton.
 * First of all, you need to implement abstract functions in your own class.
 * Secondly, you should create a page which will be your SSO host.
 *
 * @package Vinothst94\LaravelSingleSignOn
 */
class SSOClient implements SSOClientInterface
{
    /**
     * SSO host url.
     *
     * @var string
     */
    protected $ssoServerUrl;

    /**
     * Client name.
     *
     * @var string
     */
    protected $clientName;

    /**
     * Client secret token.
     *
     * @var string
     */
    protected $clientSecret;

    /**
     * User info retrieved from the SSO host.
     *
     * @var array
     */
    protected $userInfo;

    /**
     * Random token generated for the client and user.
     *
     * @var string|null
     */
    protected $token;


    public function __construct()
    {
        $this->setOptions();
        $this->saveToken();
    }

    /**
     * Attach client session to client user in SSO host.
     *
     * @return void
     */
    public function attach()
    {
        $parameters = [
            'return_url' => $this->getCurrentUrl(),
            'client' => $this->clientName,
            'token' => $this->token,
            'checksum' => hash('sha256', 'attach' . $this->token . $this->clientSecret)
        ];

        $attachUrl = $this->generateCommandUrl('attach', $parameters);

        $this->redirect($attachUrl);
    }

    /**
     * Getting user info from SSO based on user session.
     *
     * @return array
     */
    public function getUserInfo()
    {
        if (!isset($this->userInfo) || empty($this->userInfo)) {
            $this->userInfo = $this->makeRequest('GET', 'userInfo');
        }

        return $this->userInfo;
    }

    /**
     * Login client to SSO host with user credentials.
     *
     * @param string $username
     * @param string $password
     *
     * @return bool
     */
    public function login(string $username, string $password)
    {
        $this->userInfo = $this->makeRequest('POST', 'login', compact('username', 'password'));

        if (!isset($this->userInfo['error']) && isset($this->userInfo['data']['id'])) {
            return true;
        }

        return false;
    }

    /**
     * Logout client from SSO host.
     *
     * @return void
     */
    public function logout()
    {
        $this->makeRequest('POST', 'logout');
    }

    /**
     * Generate request url.
     *
     * @param string $command
     * @param array $parameters
     *
     * @return string
     */
    protected function generateCommandUrl(string $command, array $parameters = [])
    {
        $query = '';
        if (!empty($parameters)) {
            $query = '?' . http_build_query($parameters);
        }

        return $this->ssoServerUrl . '/api/sso/' . $command . $query;
    }

    /**
     * Generate session key with client name, client secret and unique client token.
     *
     * @return string
     */
    protected function getSessionId()
    {
        $checksum = hash('sha256', 'session' . $this->token . $this->clientSecret);
        return "SSO-{$this->clientName}-{$this->token}-$checksum";
    }

    /**
     * Set base class options (sso host url, client name and secret, etc).
     *
     * @return void
     *
     * @throws MissingConfigurationException
     */
    protected function setOptions()
    {
        $this->ssoServerUrl = config('laravel-sso.hostUrl', null);
        $this->clientName = config('laravel-sso.clientName', null);
        $this->clientSecret = config('laravel-sso.clientSecret', null);

        if (!$this->ssoServerUrl || !$this->clientName || !$this->clientSecret) {
            throw new MissingConfigurationException('Missing configuration values.');
        }
    }

    /**
     * Save unique client token to cookie.
     *
     * @return void
     */
    protected function saveToken()
    {
        if (isset($this->token) && $this->token) {
            return;
        }

        if ($this->token = Cookie::get($this->getCookieName(), null)) {
            return;
        }

        // If cookie token doesn't exist, we need to create it with unique token...
        $this->token = Str::random(40);
        Cookie::queue(Cookie::make($this->getCookieName(), $this->token, 60));

        // ... and attach it to user session in SSO host.
        $this->attach();
    }

    /**
     * Delete saved unique user token.
     *
     * @return void
     */
    protected function deleteToken()
    {
        $this->token = null;
        Cookie::forget($this->getCookieName());
    }

    /**
     * Make request to SSO host.
     *
     * @param string $method Request method 'post' or 'get'.
     * @param string $command Request command name.
     * @param array $parameters Parameters for URL query string if GET request and form parameters if it's POST request.
     *
     * @return array
     */
    protected function makeRequest(string $method, string $command, array $parameters = [])
    {
        $commandUrl = $this->generateCommandUrl($command);

        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '. $this->getSessionId(),
        ];

        switch ($method) {
            case 'POST':
                $body = ['form_params' => $parameters];
                break;
            case 'GET':
                $body = ['query' => $parameters];
                break;
            default:
                $body = [];
                break;
        }

        $client = new GuzzleHttp\Client;
        $response = $client->request($method, $commandUrl, $body + ['headers' => $headers]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Redirect client to specified url.
     *
     * @param string $url URL to be redirected.
     * @param array $parameters HTTP query string.
     * @param int $httpResponseCode HTTP response code for redirection.
     *
     * @return void
     */
    protected function redirect(string $url, array $parameters = [], int $httpResponseCode = 307)
    {
        $query = '';
        // Making URL query string if parameters given.
        if (!empty($parameters)) {
            $query = '?';

            if (parse_url($url, PHP_URL_QUERY)) {
                $query = '&';
            }

            $query .= http_build_query($parameters);
        }

        app()->abort($httpResponseCode, '', ['Location' => $url . $query]);
    }

    /**
     * Getting current url which can be used as return to url.
     *
     * @return string
     */
    protected function getCurrentUrl()
    {
        return url()->full();
    }

    /**
     * Cookie name in which we save unique client token.
     *
     * @return string
     */
    protected function getCookieName()
    {
        // Cookie name based on client's name because there can be some clients on same domain
        // and we need to prevent duplications.
        return 'sso_token_' . preg_replace('/[_\W]+/', '_', strtolower($this->clientName));
    }
}
