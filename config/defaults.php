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
    'network_policy' => [
        'rules' => [
            'DHCP_Ingress' => [
                'name' => 'DHCP_Ingress',
                'sequence' => 5001,
                'source' =>  '10.0.0.2',
                'destination' => 'ANY',
                'action' => 'ALLOW',
                'direction' => 'IN',
                'enabled' => true,
                'type' => 'DHCP_Ingress',
            ],
            'DHCP_Egress' => [
                'name' => 'DHCP_Egress',
                'sequence' => 5002,
                'source' =>  'ANY',
                'destination' => 'ANY',
                'action' => 'ALLOW',
                'direction' => 'OUT',
                'enabled' => true,
                'type' => 'DHCP_Egress',
            ]
        ]
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
