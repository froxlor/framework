<?php

return [
    'node' => env('DEV_NODE', 'local'),
    'repositories' => env('DEV_REPOSITORIES', 'framework'),
    'packages' => env('DEV_PACKAGES', ''),
    'first_name' => env('DEV_FIRST_NAME', 'froxlor'),
    'last_name' => env('DEV_LAST_NAME', 'Super-Admin'),
    'email' => env('DEV_EMAIL', 'dev@froxlor.org'),
    'password' => env('DEV_PASSWORD', 'DtQOWsmW9eH3rrlA9uujhDmY'),
    'seed_development_data' => env('FROXLOR_SEED_DEVELOPMENT_DATA', false),
];
