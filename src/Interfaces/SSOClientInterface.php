<?php

namespace VinothST94\LaravelSingleSignOn\Interfaces;

interface SSOClientInterface
{
    /**
     * Attach user session to client session in SSO host.
     *
     * @return void
     */
    public function attach();

    /**
     * Getting user info from SSO based on user session.
     *
     * @return array
     */
    public function getUserInfo();

    /**
     * Login user to SSO host with user credentials.
     *
     * @param string $username
     * @param string $password
     *
     * @return bool
     */
    public function login(string $username, string $password);

    /**
     * Logout user from SSO host.
     *
     * @return void
     */
    public function logout();
}
