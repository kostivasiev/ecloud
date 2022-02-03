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
    'floating-ip' => [
        'nat' => [
            'sequence' => 1000
        ],
        'rdns' => [
            'default_hostname' => '4.3.2.1.svrlist.ukfast.net',
            'dns_suffix' => 'inaddr.appr'
        ]
    ],
    'firewall_rule' => [
        /** The defaults for these need review as unsure of what they're supposed to be */
        'egress' => '',
        'ingress' => '',
    ],
    'network_policy' => [
        'rules' => [
            'dhcp_ingress' => [
                'name' => 'dhcp_ingress',
                'sequence' => 10000,
                'source' =>  '10.0.0.2',
                'destination' => 'ANY',
                'action' => 'ALLOW',
                'direction' => 'IN',
                'enabled' => true,
                'type' => NetworkRule::TYPE_DHCP,
                'ports' => [
                    [
                        // DHCP Server
                        'protocol' => 'UDP',
                        'source' => 'ANY',
                        'destination' => 67
                    ],
                    [
                        // DHCP Client
                        'protocol' => 'UDP',
                        'source' => 'ANY',
                        'destination' => 68
                    ],
                ]
            ],
            'dhcp_egress' => [
                'name' => 'dhcp_egress',
                'sequence' => 10001,
                'source' =>  'ANY',
                'destination' => 'ANY',
                'action' => 'ALLOW',
                'direction' => 'OUT',
                'enabled' => true,
                'type' => NetworkRule::TYPE_DHCP,
                'ports' => [
                    [
                        // DHCP Server
                        'protocol' => 'UDP',
                        'source' => 'ANY',
                        'destination' => 67
                    ],
                    [
                        // DHCP Client
                        'protocol' => 'UDP',
                        'source' => 'ANY',
                        'destination' => 68
                    ],
                ]
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
    'vpn_session' => [
        'network' => [
            'nosnat' => [
                'sequence' => 500
            ]
        ]
    ],
    'tag' => [
        'scope' => 'ukfast',
        'networking' => [
            'default' => env('TAG_DEFAULT_NETWORKING', 'az-default'),
            'advanced' => env('TAG_ADVANCED_NETWORKING', 'az-advancedNetworking'),
        ]
    ]
];
