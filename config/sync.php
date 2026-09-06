<?php

return [

    /*
    |--------------------------------------------------------------------------
    | External Server URL
    |--------------------------------------------------------------------------
    | The base URL of the external server to sync with (no trailing slash).
    */
    'external_server_url' => env('SYNC_EXTERNAL_URL', 'http://207.231.110.79'),

    /*
    |--------------------------------------------------------------------------
    | Sync Token
    |--------------------------------------------------------------------------
    | A shared secret token used for server-to-server authentication.
    | Generate one: php artisan tinker -> Str::random(64)
    */
    'sync_token' => env('SYNC_TOKEN', ''),

];
