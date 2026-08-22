<?php

return [

    'discovery' => rtrim(env('FROXLOR_PACKAGES_DISCOVERY', 'https://packages.froxlor.org'), '/'),

    'token' => env('FROXLOR_PACKAGES_TOKEN', null),

    'directory' => env('FROXLOR_PACKAGES_DIRECTORY', '/opt/froxlor/packages'),

    // Marketplace metadata (title, description, price, header image, ...) for packages listed
    // in the discovery repository. Falls back to resources/marketplace.json (see
    // MarketplaceService) when this can't be reached, e.g. for local development/testing.
    'marketplace' => env('FROXLOR_PACKAGES_MARKETPLACE', 'https://packages.froxlor.org/v3/marketplace.json'),
];
