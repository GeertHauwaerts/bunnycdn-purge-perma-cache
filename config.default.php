<?php

return [
    'app_signing_key' => 'random-string-for-signing-requests-and-responses',
    'bunny_api_key' => 'bunny-api-key',
    'storage_zones' => [
        'bunnycdn-storage-zone-name' => [
            'api_key' => 'bunnycdn-storage-zone-api-key',
            'region' => 'de',
        ],
        'myzone' => [
            'api_key' => 'DRSotesMceBeBKGvsiybpIfbarHAKFwz',
            'region' => 'de',
        ],
        'myotherzone' => [
            'api_key' => 'CWMwE8m2HcFtMepLD5FfRLfaS9IBWwTj',
            'region' => 'de',
        ],
    ],
    'redis' => [
        'host' => 'bppc-redis',
    ],
    'test_command' => [
        'service_url' => 'http://bppc-api:8000',
        'tests' => [
            ['bunnycdn-storage-zone-name' => 'test/path1/1.jpg'],
            ['myzone' => 'test/path2/2.jpg'],
            ['myotherzone' => 'test/path2/2.jpg'],
        ],
    ],
];
