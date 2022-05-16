<?php

namespace Vinothst94\LaravelSingleSignOn\Middleware;

use Closure;
use Illuminate\Http\Request;
use Vinothst94\LaravelSingleSignOn\SSOClient;

class SSOAutoLogin
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $client = new SSOClient();
        $response = $client->getUserInfo();

        // If user is logged out in SSO host but still logged in client.
        if (!isset($response['data']) && !auth()->guest()) {
            return $this->logout($request);
        }

        // If there is a problem with data in SSO host, we will re-attach user session.
        if (isset($response['error']) && strpos($response['error'], 'There is no saved session data associated with the client session id') !== false) {
            return $this->clearSSOCookie($request);
        }

        // If user is logged in SSO host and didn't logged in client...
        if (isset($response['data']) && (auth()->guest() || auth()->user()->id != $response['data']['id'])) {
            // ... we will authenticate our user.
            auth()->loginUsingId($response['data']['id']);
        }

        return $next($request);
    }

    /**
     * Clearing SSO cookie so client will re-attach SSO host session.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function clearSSOCookie(Request $request)
    {
        return redirect($request->fullUrl())->cookie(cookie('sso_token_' . config('laravel-sso.clientName')));
    }

    /**
     * Logging out authenticated user.
     * Need to make a page refresh because current page may be accessible only for authenticated users.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function logout(Request $request)
    {
        auth()->logout();
        return redirect($request->fullUrl());
    }
}
