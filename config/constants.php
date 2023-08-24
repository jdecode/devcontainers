<?php

return [

    /*
     |--------------------------------------------------------------------------
     | Default Boilerplate values
     |--------------------------------------------------------------------------
     |
     | Here you can specify all custom values you want to use in the code
     |
     */

    'roles' => [
        'super_admin' => 'super_admin',
        'user' => 'user'
    ],
    'api_rate_limit' => env('API_RATE_LIMIT', 60),
    'user' => [
        'profile_image' => [
            'path' => 'users/profile-images/',
            'thumbnail_path' => 'users/profile-images/thumbnails/',
            'max' => 10240,
            'thumbnail_width_px' => 32,
            'thumbnail_height_px' => 32,
            'image_width_px' => 512,
            'image_height_px' => 512
        ]
    ]
];
