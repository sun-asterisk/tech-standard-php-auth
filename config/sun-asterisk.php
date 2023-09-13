<?php

return [
    'auth' => [
        /*
        |---------------------------------------------------------
        | Attribute login
        |---------------------------------------------------------
        |
        | E.g. 'email or username'
        |
        */
        'login_username' => 'email',
        /*
        |---------------------------------------------------------
        | Attribute field_credentials
        |---------------------------------------------------------
        | Use 1 of the list for authentication
        | E.g. 'username or email or phone'
        |
        */
        'field_credentials' => [
            'email',
        ],
        /*
        |---------------------------------------------------------
        | Attribute field_payload_credentials
        |---------------------------------------------------------
        | Use the items in the list to create an access token
        | 
        | E.g. 'id or email'
        |
        */
        'payload_credentials' => [
            'id',
            'email',
        ],
        /*
        |---------------------------------------------------------
        | Attribute login
        |---------------------------------------------------------
        |
        | E.g. 'password or passwd'
        |
        */
        'login_password' => 'password',
        /*
        |---------------------------------------------------------
        | Model login
        |---------------------------------------------------------
        |
        | E.g. 'App\Models\User::class or App\Models\Admin::class'
        |
        */
        'model' => App\Models\User::class,
        /*
        |---------------------------------------------------------
        | Token forgot password
        |---------------------------------------------------------
        |
        | Default 5 minutes
        | E.g. '5'
        |
        */
        'token_expires' => 5, // minutes
        /*
        |---------------------------------------------------------
        | Key for jwt access token
        |---------------------------------------------------------
        |
        | E.g. 'xxxx'
        |
        */
        'jwt_key' => 'jwt_key',
        /*
        |---------------------------------------------------------
        | Key for jwt refresh access token
        |---------------------------------------------------------
        |
        | E.g. 'xxxx'
        |
        */
        'jwt_refresh_key' => 'jwt_refresh_key',
        /*
        |---------------------------------------------------------
        | TTL for jwt
        |---------------------------------------------------------
        |
        | Default 60 minutes
        | E.g. '60'
        |
        */
        'jwt_ttl' => 60, // minutes
        /*
        |---------------------------------------------------------
        | TTL for refresh access token
        |---------------------------------------------------------
        |
        | Default 20160 minutes
        | E.g. '60'
        |
        */
        'jwt_refresh_ttl' => 20160, // minutes
        /*
        |---------------------------------------------------------
        | use Socialite Providers for social login
        |---------------------------------------------------------
        |
        | Default false
        |
        */
        'enabled_social' => false,
    ],
];
