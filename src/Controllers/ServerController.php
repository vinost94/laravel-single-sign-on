<?php

namespace Vinothst94\LaravelSingleSignOn\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Vinothst94\LaravelSingleSignOn\SSOHost;

class ServerController extends BaseController
{
    /**
     * @param Request $request
     * @param SSOHost $host
     *
     * @return void
     */
    public function attach(Request $request, SSOHost $host)
    {
        $host->attach(
            $request->get('client', null),
            $request->get('token', null),
            $request->get('checksum', null)
        );
    }

    /**
     * @param Request $request
     * @param SSOHost $host
     *
     * @return mixed
     */
    public function login(Request $request, SSOHost $host)
    {
        return $host->login(
            $request->get('username', null),
            $request->get('password', null)
        );
    }

    /**
     * @param SSOHost $host
     *
     * @return string
     */
    public function logout(SSOHost $host)
    {
        return $host->logout();
    }

    /**
     * @param SSOHost $host
     *
     * @return string
     */
    public function userInfo(SSOHost $host)
    {
        return $host->checkUserApplicationAuth();
    }
}
