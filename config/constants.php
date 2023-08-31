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
            'image_params' => [
                'path' => 'users/profile-images/',
                'width_px' => 512,
                'height_px' => 512,
            ],
            'thumbnail_params' => [
                'path' => 'users/profile-images/thumbnails/',
                'width_px' => 32,
                'height_px' => 32,
            ],
            'max_file_size' => 10240,
        ]
    ],
    'pagination' => [
        'default_per_page' => 10,
    ]
];
