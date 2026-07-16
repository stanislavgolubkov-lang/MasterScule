<?php

return [
    'name' => env('STORE_NAME', 'MasterScule'),
    'domain_label' => env('STORE_DOMAIN_LABEL', 'MasterScule.md'),
    'country' => env('STORE_COUNTRY', 'Moldova'),
    'currency' => env('STORE_CURRENCY', 'MDL'),
    'phone' => env('STORE_PHONE', '+373 781000 99'),
    'phone_href' => env('STORE_PHONE_HREF', '+37378100099'),
    'email' => env('STORE_EMAIL', 'info@masterscule.md'),
    'address' => env('STORE_ADDRESS', 'Alexandru cel Bun 103/A, Chișinău, Republica Moldova'),
    'working_hours' => [
        'ru' => env('STORE_HOURS_RU', 'Пн - Пт 08:00 - 17:00'),
        'ro' => env('STORE_HOURS_RO', 'Luni - Vineri 08:00 - 17:00'),
    ],
    'legal_name' => env('STORE_LEGAL_NAME', 'MasterScule Moldova'),
];
