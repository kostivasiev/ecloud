<?php

return [
    'customer_max_per_az' => env('LOAD_BALANCER_CUSTOMER_MAX_PER_AZ', 8),
    'version' => env('LOAD_BALANCER_IMAGE_VERSION', 'Ubuntu-20.04-LBv2')
];
