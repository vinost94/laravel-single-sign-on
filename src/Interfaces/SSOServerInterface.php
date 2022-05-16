<?php

namespace VinothST94\LaravelSingleSignOn\Interfaces;

interface SSOServerInterface
{
    /**
     * Attach user's session to client's session.
     *
     * @param string|null $client Client's name/id.
     * @param string|null $token Token sent from client.
     * @param string|null $checksum Calculated client+token checksum.
     *
     * @return mixed
     */
    public function attach(?string $client, ?string $token, ?string $checksum);

    /**
     * Login user with provided data.
     *
     * @param string $username User's username.
     * @param string $password User's password.
     *
     * @return mixed
     */
    public function login(?string $username, ?string $password);

    /**
     * Logging out user.
     *
     * @return string Json response.
     */
    public function logout();

    /**
     * Return user info based on client session id associated with client session id.
     *
     * @return string Json response.
     */
    public function userInfo();
}
