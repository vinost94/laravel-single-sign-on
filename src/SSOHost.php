<?php

namespace Vinothst94\LaravelSingleSignOn;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Vinothst94\LaravelSingleSignOn\Resources\UserResource;
use Vinothst94\LaravelSingleSignOn\Exceptions\SSOHostException;
use Vinothst94\LaravelSingleSignOn\Interfaces\SSOHostInterface;

class SSOHost implements SSOHostInterface
{
    /**
     * @var mixed
     */
    protected $clientId;

    /**
     * Attach user's session to client's session.
     *
     * @param string|null $client Client's name/id.
     * @param string|null $token Token sent from client.
     * @param string|null $checksum Calculated client+token checksum.
     *
     * @return string or redirect
     */
    public function attach(?string $client, ?string $token, ?string $checksum)
    {
        try {
            if (!$client) {
                $this->fail('No client id specified.', true);
            }

            if (!$token) {
                $this->fail('No token specified.', true);
            }

            if (!$checksum || $checksum != $this->generateAttachChecksum($client, $token)) {
                $this->fail('Invalid checksum.', true);
            }

            $this->startUserSession();
            $sessionId = $this->generateSessionId($client, $token);

            $this->saveClientSessionData($sessionId, $this->getSessionData('id'));
        } catch (SSOHostException $e) {
            return $this->redirect(null, ['sso_error' => $e->getMessage()]);
        }

        $this->attachSuccess();
    }

    /**
     * @param null|string $username
     * @param null|string $password
     *
     * @return string
     */
    public function login(?string $username, ?string $password)
    {
        try {
            $this->startClientSession();

            if (!$username || !$password) {
                $this->fail('No username and/or password provided.');
            }

            if (!$this->authenticate($username, $password)) {
                $this->fail('User authentication failed.');
            }
        } catch (SSOHostException $e) {
            return $this->returnJson(['error' => $e->getMessage()]);
        }

        $this->setSessionData('sso_user', $username);

        return $this->userInfo();
    }

    /**
     * Logging user out.
     *
     * @return string
     */
    public function logout()
    {
        try {
            $this->startClientSession();
            $this->setSessionData('sso_user', null);
        } catch (SSOHostException $e) {
            return $this->returnJson(['error' => $e->getMessage()]);
        }

        return $this->returnJson(['success' => 'User has been successfully logged out.']);
    }

    /**
     * Returning user info for the client.
     *
     * @return string
     */
    public function userInfo()
    {
        try {
            $this->startClientSession();

            $username = $this->getSessionData('sso_user');

            if (!$username) {
                $this->fail('User not authenticated. Session ID: ' . $this->getSessionData('id'));
            }

            if (!$user = $this->getUserInfo($username)) {
                $this->fail('User not found.');
            }
        } catch (SSOHostException $e) {
            return $this->returnJson(['error' => $e->getMessage()]);
        }

        return $this->returnUserInfo($user);
    }

    /**
     * Resume client session if saved session id exist.
     *
     * @throws SSOHostException
     *
     * @return void
     */
    protected function startClientSession()
    {
        if (isset($this->clientId)) {
            return;
        }

        $sessionId = $this->getClientSessionId();

        if (!$sessionId) {
            $this->fail('Missing session key from client.');
        }

        $savedSessionId = $this->getClientSessionData($sessionId);

        if (!$savedSessionId) {
            $this->fail('There is no saved session data associated with the client session id.');
        }

        $this->startSession($savedSessionId);

        $this->clientId = $this->validateClientSessionId($sessionId);
    }

    /**
     * Check if client session is valid.
     *
     * @param string $sessionId Session id from the client.
     *
     * @throws SSOHostException
     *
     * @return string
     */
    protected function validateClientSessionId(string $sessionId)
    {
        $matches = null;

        if (!preg_match('/^SSO-(\w*+)-(\w*+)-([a-z0-9]*+)$/', $this->getClientSessionId(), $matches)) {
            $this->fail('Invalid session id');
        }

        if ($this->generateSessionId($matches[1], $matches[2]) != $sessionId) {
            $this->fail('Checksum failed: Client IP address may have changed');
        }

        return $matches[1];
    }

    /**
     * Generate session id from session token.
     *
     * @param string $clientId
     * @param string $token
     *
     * @throws SSOHostException
     *
     * @return string
     */
    protected function generateSessionId(string $clientId, string $token)
    {
        $client = $this->getClientInfo($clientId);

        if (!$client) {
            $this->fail('Provided client does not exist.');
        }

        return 'SSO-' . $clientId . '-' . $token . '-' . hash('sha256', 'session' . $token . $client['secret']);
    }

    /**
     * Generate session id from session token.
     *
     * @param string $clientId
     * @param string $token
     *
     * @throws SSOHostException
     *
     * @return string
     */
    protected function generateAttachChecksum($clientId, $token)
    {
        $client = $this->getClientInfo($clientId);

        if (!$client) {
            $this->fail('Provided client does not exist.');
        }

        return hash('sha256', 'attach' . $token . $client['secret']);
    }

    /**
     * Do things if attaching was successful.
     *
     * @return void
     */
    protected function attachSuccess()
    {
        $this->redirect();
    }

    /**
     * If something failed, throw an Exception or redirect.
     *
     * @param null|string $message
     * @param bool $isRedirect
     * @param null|string $url
     *
     * @throws SSOHostException
     *
     * @return void
     */
    protected function fail(?string $message, bool $isRedirect = false, ?string $url = null)
    {
        if (!$isRedirect) {
            throw new SSOHostException($message);
        }

        $this->redirect($url, ['sso_error' => $message]);
    }

    /**
     * Redirect to provided URL with query string.
     *
     * If $url is null, redirect to url which given in 'return_url'.
     *
     * @param string|null $url URL to be redirected.
     * @param array $parameters HTTP query string.
     * @param int $httpResponseCode HTTP response code for redirection.
     *
     * @return void
     */
    protected function redirect(?string $url = null, array $parameters = [], int $httpResponseCode = 307)
    {
        if (!$url) {
            $url = urldecode(request()->get('return_url', null));
        }

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
     * Returning json response for the client.
     *
     * @param null|array $response Response array which will be encoded to json.
     * @param int $httpResponseCode HTTP response code.
     *
     * @return string
     */
    protected function returnJson(?array $response = null, int $httpResponseCode = 200)
    {
        return response()->json($response, $httpResponseCode);
    }

    /**
     * Authenticate using user credentials
     *
     * @param string $username
     * @param string $password
     *
     * @return bool
     */
    protected function authenticate(string $username, string $password)
    {
        if (!Auth::attempt(['email' => $username, 'password' => $password])) {
            return false;
        }

        // After authentication Laravel will change session id, but we need to keep
        // this the same because this session id can be already attached to other clients.
        $sessionId = $this->getClientSessionId();
        $savedSessionId = $this->getClientSessionData($sessionId);
        $this->startSession($savedSessionId);

        return true;
    }

    /**
     * Get the secret key and other info of a client
     *
     * @param string $clientId
     *
     * @return null|array
     */
    protected function getClientInfo(string $clientId)
    {
        try {
            $client = config('laravel-sso.clientsModel')::where('name', $clientId)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return null;
        }

        return $client;
    }

    /**
     * Get the information about a user
     *
     * @param string $username
     *
     * @return array|object|null
     */
    protected function getUserInfo(string $username)
    {
        try {
            $user = config('laravel-sso.usersModel')::where('email', $username)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return null;
        }

        return $user;
    }

    /**
     * Returning user info for client. Should return json or something like that.
     *
     * @param array|object $user Can be user object or array.
     *
     * @return array|object|UserResource
     */
    protected function returnUserInfo($user)
    {
        return new UserResource($user);
    }

    /**
     * Return session id sent from client.
     *
     * @return null|string
     */
    protected function getClientSessionId()
    {
        $authorization = request()->header('Authorization', null);
        if ($authorization &&  strpos($authorization, 'Bearer') === 0) {
            return substr($authorization, 7);
        }

        return null;
    }

    /**
     * Start new session when user visits server.
     *
     * @return void
     */
    protected function startUserSession()
    {
        // Session must be started by middleware.
    }

    /**
     * Set session data
     *
     * @param string $key
     * @param null|string $value
     *
     * @return void
     */
    protected function setSessionData(string $key, ?string $value = null)
    {
        if (!$value) {
            Session::forget($key);
            return;
        }

        Session::put($key, $value);
    }

    /**
     * Get data saved in session.
     *
     * @param string $key
     *
     * @return string
     */
    protected function getSessionData(string $key)
    {
        if ($key === 'id') {
            return Session::getId();
        }

        return Session::get($key, null);
    }

    /**
     * Start new session with specific session id.
     *
     * @param $sessionId
     *
     * @return void
     */
    protected function startSession(string $sessionId)
    {
        Session::setId($sessionId);
        Session::start();
    }

    /**
     * Save client session data to cache.
     *
     * @param string $clientSessionId
     * @param string $sessionData
     *
     * @return void
     */
    protected function saveClientSessionData(string $clientSessionId, string $sessionData)
    {
        Cache::put('client_session:' . $clientSessionId, $sessionData, now()->addHour());
    }

    /**
     * Get client session data from cache.
     *
     * @param string $clientSessionId
     *
     * @return null|string
     */
    protected function getClientSessionData(string $clientSessionId)
    {
        return Cache::get('client_session:' . $clientSessionId);
    }

    /**
     * Check for the User authorization with application and return error or userinfo
     *
     * @return string
     */
    public function checkUserApplicationAuth()
    {
        try {
            if (empty($this->checkClientUserAuthentication())) {
                $this->fail('User authorization failed with application.');
            }
        } catch (SSOHostException $e) {
            return $this->returnJson(['error' => $e->getMessage()]);
        }
        return $this->userInfo();
    }

    /**
     * Returning the client details
     *
     * @return string
     */
    public function getClientDetail()
    {
        return $this->getClientInfo($this->clientId);
    }

    /**
     * Check for User Auth with Client Application.
     *
     * @return boolean
     */
    protected function checkClientUserAuthentication()
    {
        $userInfo = $this->userInfo();
        $client = $this->getClientDetail();
        if (!empty($userInfo->id) && !empty($client)) {
            $clientUser = config('laravel-sso.clientsUserModel')::where('user_id', $userInfo->id)->where('client_id', $client->id)->first();
            if (empty($clientUser)) {
                return false;
            }
        }
        return true;
    }
}
