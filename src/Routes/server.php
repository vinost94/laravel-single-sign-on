<?php

/**
 * Routes which is neccessary for the SSO server.
 */

Route::middleware('api')->prefix('api/sso')->group(function () {
    Route::post('login', [Vinothst94\LaravelSingleSignOn\Controllers\ServerController, 'login']);
    Route::post('logout', [Vinothst94\LaravelSingleSignOn\Controllers\ServerController, 'logout']);
    Route::get('attach', [Vinothst94\LaravelSingleSignOn\Controllers\ServerController, 'attach']);
    Route::get('userInfo', [Vinothst94\LaravelSingleSignOn\Controllers\ServerController, 'userInfo']);
});
