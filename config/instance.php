<?php

return [
    'cpu_cores' => [
        'min' => env('CPU_CORES_MIN', 1),
        'max' => env('CPU_CORES_MAX', 10),
    ],
    'ram_capacity' => [
        'min' => env('RAM_MIN', 1024),
        'max' => env('RAM_MAX', 65536),
    ],
    'nics' => [
        'max' => env('NICS_MAX', 5),
    ],
    'resource_tier_tags' => [
        'standard_cpu'
    ],
    'max_limit' => [
        'per_vpc' => env('MAX_INSTANCE_PER_VPC', 80),
        'total' => env('MAX_INSTANCE_TOTAL', 400),
    ],
    'guest_admin_username' => [
        'windows' => env('GUEST_ADMIN_USERNAME_WINDOWS', 'graphite.rack'),
        'linux' => env('GUEST_ADMIN_USERNAME_LINUX', 'root')
    ],
    'guest_support_username' => [
        'windows' => env('GUEST_SUPPORT_USERNAME_WINDOWS', 'ukfast.support'),
        'linux' => env('GUEST_SUPPORT_USERNAME_LINUX', 'ukfastsupport')
    ]
];
