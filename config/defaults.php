<?php

use App\Models\V2\NetworkRule;

return [
    'availability_zones' => [],
    'vpc' => [
        'max_count' => env('VPC_MAX_COUNT', 10),
    ],
    'ssh_key_pair' => [
        'max_count' => env('SSH_KEY_PAIR_MAX_COUNT', 30),
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
            'dhcp_ingress' => [
                'name' => NetworkRule::TYPE_DHCP_INGRESS,
                'sequence' => 10000,
                'source' =>  '10.0.0.2',
                'destination' => 'ANY',
                'action' => 'ALLOW',
                'direction' => 'IN',
                'enabled' => true,
                'type' => NetworkRule::TYPE_DHCP_INGRESS,
            ],
            'dhcp_egress' => [
                'name' => NetworkRule::TYPE_DHCP_EGRESS,
                'sequence' => 10001,
                'source' =>  'ANY',
                'destination' => 'ANY',
                'action' => 'ALLOW',
                'direction' => 'OUT',
                'enabled' => true,
                'type' => NetworkRule::TYPE_DHCP_EGRESS,
            ],
            'catchall' => [
                'name' => NetworkRule::TYPE_CATCHALL,
                'sequence' => 20000,
                'source' =>  'ANY',
                'destination' => 'ANY',
                'action' => 'REJECT',
                'direction' => 'IN_OUT',
                'enabled' => true,
                'type' => NetworkRule::TYPE_CATCHALL,
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
