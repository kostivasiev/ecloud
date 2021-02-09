<?php

return [
    'capacity' => [
        'windows' => [
            'min' => env('VOLUME_CAPACITY_WINDOWS_MIN', 40),
        ],
        'linux' => [
            'min' => env('VOLUME_CAPACITY_LINUX_MIN', 20),
        ],
        'min' => env('VOLUME_CAPACITY_MIN', 1),
        'max' => env('VOLUME_CAPACITY_MAX', 1000),
    ],
    'instance' => [
        'limit' => 15,
    ]
];
