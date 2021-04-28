<?php

return [
    'availability_zones' => [],
    'vpc' => [
        'max_count' => env('VPC_MAX_COUNT', 10),
    ],
    'network' => [
        'subnets' => [
            'range' => '10.0.0.0/24',
            'dhcp_config' => [
                'lease_time' => 604800,
                'dns_servers' => [
                    '81.201.138.244',
                    '94.229.163.244'
                ]
            ],
        ],
        'domain_name' => 'ecloud.ukfast'
    ],
    'dhcp' => [
        'lease_time' => 604800,
    ],
    'vpn' => [],
    'instance' => [],
    'floating-ip' => [],
    'firewall_rule' => [
        /** The defaults for these need review as unsure of what they're supposed to be */
        'egress' => '',
        'ingress' => '',
    ],
    'region' => [],
    'router' => [
        'policy' => '20/20',
    ],
    'gateway' => [],
    'tag' => [
        'scope' => 'ukfast'
    ]
];
