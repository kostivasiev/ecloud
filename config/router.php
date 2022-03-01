<?php

return [
    'throughput' => [
        'default' => [
            'bandwidth' => env('ROUTER_THROUGHPUT_DEFAULT', 25)
        ],
        'admin_default' => [
            'bandwidth' => env('ROUTER_THROUGHPUT_ADMIN_DEFAULT', 2560)
        ],
    ]
];
