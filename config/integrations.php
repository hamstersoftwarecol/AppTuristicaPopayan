<?php

return [
    'wompi' => [
        'enabled' => env('WOMPI_ENABLED', false),
        'environment' => env('WOMPI_ENVIRONMENT', 'sandbox'),
        'public_key' => env('WOMPI_PUBLIC_KEY', ''),
        'private_key' => env('WOMPI_PRIVATE_KEY', ''),
        'integrity_secret' => env('WOMPI_INTEGRITY_SECRET', ''),
        'events_secret' => env('WOMPI_EVENTS_SECRET', ''),
    ],
    'netactica' => [
        'name' => 'Netactica Colombia',
        'base_url' => env('NETACTICA_BASE_URL'),
        'documentation' => 'https://www.netactica.co/',
    ],
    'avianca_link' => [
        'name' => 'Avianca Link',
        'base_url' => env('AVIANCA_LINK_BASE_URL'),
        'documentation' => 'https://www.avianca.com/',
    ],
];
