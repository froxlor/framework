<?php

return [

    'discovery' => rtrim(env('FROXLOR_PACKAGES_DISCOVERY', 'https://packages.froxlor.org'), '/'),

    'token' => env('FROXLOR_PACKAGES_TOKEN', null),

];
