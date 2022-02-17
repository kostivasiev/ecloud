<?php

return [
    'customer_max_per_az' => env('LOAD_BALANCER_CUSTOMER_MAX_PER_AZ', 8),
    'nats_proxy_ip' => [
        'standard' => '192.168.0.4',
        'advanced' => '192.168.128.4',
    ],
    'limits' => [
        'vips-max' => env('LOAD_BALANCER_VIPS_MAX', 10),
    ],
];
