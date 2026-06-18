<?php

return [
    'node' => env('DEV_NODE', 'local'),
    'repositories' => env('DEV_REPOSITORIES', 'framework'),
    'packages' => env('DEV_PACKAGES'),
    'first_name' => env('DEV_FIRST_NAME', 'froxlor'),
    'last_name' => env('DEV_LAST_NAME', 'Super-Admin'),
    'email' => env('DEV_EMAIL'),
    'password' => env('DEV_PASSWORD'),
    'seed_development_data' => env('DEV_SEED_DEVELOPMENT_DATA', false),
];
