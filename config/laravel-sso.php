<?php

return [
    /*
     |--------------------------------------------------------------------------
     | Laravel SSO Settings
     |--------------------------------------------------------------------------
     |
     | Set type of this web page. Possible options are: 'host' and 'client'.
     |
     | You must specify either 'host' or 'client'.
     |
     */

    'type' => 'host',

    /*
     |--------------------------------------------------------------------------
     | Settings necessary for the SSO host.
     |--------------------------------------------------------------------------
     |
     | These settings should be changed if this page is working as SSO host.
     |
     */

    'usersModel' => \App\Models\User::class,
    'clientsModel' => Vinothst94\LaravelSingleSignOn\Models\Client::class,
    'clientsUserModel' => Vinothst94\LaravelSingleSignOn\Models\ClientUser::class,

    // Table used in Vinothst94\LaravelSingleSignOn\Models\Client model
    'clientsTable' => 'clients',
    'clientUserTable' => 'client_user',

    // Logged in user fields sent to clients.
    'userFields' => [
        // Return array field name => database column name
        'id' => 'id',
    ],

    /*
     |--------------------------------------------------------------------------
     | Settings necessary for the SSO client.
     |--------------------------------------------------------------------------
     |
     | These settings should be changed if this page is working as SSO client.
     |
     */

    'serverUrl' => env('SSO_SERVER_URL', null),
    'clientName' => env('SSO_CLIENT_NAME', null),
    'clientSecret' => env('SSO_CLIENT_SECRET', null),
];
