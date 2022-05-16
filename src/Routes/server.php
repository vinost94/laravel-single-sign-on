<?php

/**
 * Routes which is neccessary for the SSO server.
 */

Route::middleware('api')->prefix('api/sso')->group(function () {
    Route::post('login', [Vinothst94\LaravelSingleSignOn\Controllers\ServerController::class, 'login']);
    Route::post('logout', [Vinothst94\LaravelSingleSignOn\Controllers\ServerController::class, 'logout']);
    Route::get('attach', [Vinothst94\LaravelSingleSignOn\Controllers\ServerController::class, 'attach']);
    Route::get('userInfo', [Vinothst94\LaravelSingleSignOn\Controllers\ServerController::class, 'userInfo']);
});
