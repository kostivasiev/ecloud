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
            'default_rdns' => '%s.srvlist.ukfast.net',
            'dns_suffix' => '%s.in-addr.arpa'
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
            ],
            [
                'name' => 'Logic Monitor Collector',
                'action' => 'ALLOW',
                'sequence' => 10002,
                'direction' => 'IN',
                'enabled' => true,
                'source' => null,
                'destination' => 'ANY',
                'ports' => [
                    [
                        'protocol' => 'ICMPv4'
                    ],
                    [
                        'protocol' => 'TCP',
                        'source' => 'ANY',
                        'destination' => 2020
                    ],
                    [
                        'protocol' => 'TCP',
                        'source' => 'ANY',
                        'destination' => 5986
                    ],
                    [
                        'protocol' => 'UDP',
                        'source' => 'ANY',
                        'destination' => 161
                    ]
                ]
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
            'management' => [
                'default' => env('TAG_DEFAULT_NETWORKING', 'az-admin'),
                'advanced' => env('TAG_ADVANCED_NETWORKING', 'az-adminadv'),
            ]
        ],
        'edge-cluster' => [
            'default' => env('TAG_DEFAULT_EDGECLUSTER', 'az-default'),
            'advanced' => env('TAG_ADVANCED_EDGECLUSTER', 'az-advancedNetworking'),
            'management' => [
                'default' => env('TAG_DEFAULT_EDGECLUSTER', 'az-admin'),
                'advanced' => env('TAG_ADVANCED_EDGECLUSTER', 'az-adminadv'),
            ]
        ]
    ]
];
