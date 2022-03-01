<?php

return [
    'from' => 'alerts@ukfast.co.uk',
    'capacity' => [
        'floating_ip' => [
            'to' => 'networkteam@ukfast.co.uk',
            'cc' => 'enterprise-infrastructure@ukfast.co.uk',
        ],
        'default' => [
            'to' => 'enterprise-infrastructure@ukfast.co.uk',
        ],
        'dev' => [
            'to' => [
                'paul.mcnally@ukfast.co.uk'
            ]
        ]
    ],
    'billing' => [
        'to' => 'gavin.taylor@ukfast.co.uk'
    ],
    'health' => [
        'to' => [
            'gavin.taylor@ukfast.co.uk',
            'lee.spottiswood@ukfast.co.uk',
        ]
    ]
];
