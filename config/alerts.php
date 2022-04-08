<?php

return [
    'from' => 'alerts@ukfast.co.uk',
    'capacity' => [
        'floating_ip' => [
            'to' => 'networkteam@ans.co.uk',
            'cc' => 'enterprise-infrastructure@ans.co.uk',
        ],
        'default' => [
            'to' => 'enterprise-infrastructure@ans.co.uk',
        ],
        'dev' => [
            'to' => [
                'paul.mcnally@ans.co.uk'
            ]
        ]
    ],
    'billing' => [
        'to' => 'ecloud-billing@ans.co.uk'
    ],
    'health' => [
        'to' => [
            'gavin.taylor@ans.co.uk',
            'lee.spottiswood@ans.co.uk',
        ]
    ]
];
