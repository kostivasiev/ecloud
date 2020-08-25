<?php

return [
    'availability_zones' => [],
    'vpc'                => [],
    'network'            => [
        'subnets' => [
            'gateway_address' => '10.0.0.1/24',
            'dhcp_config' => [
                'server_address' => '10.0.0.2',
                'lease_time'     => 604800,
                'dns_servers' => [
                    '81.201.138.244',
                    '94.229.163.244'
                ]
            ],
        ],
        'domain_name' => 'ecloud.ukfast'
    ],
    'dhcp'               => [
        'server_addresses' => ['192.168.0.0/20'],
        'lease_time'       => 604800,
    ],
    'vpn'                => [],
    'instance'           => [],
    'floating-ip'        => [],
    'firewall_rule'      => [
        /** The defaults for these need review as unsure of what they're supposed to be */
        'egress' => '',
        'ingress' => '',
    ],
    'region'             => [],
    'router'             => [
        'policy' => '20/20',
    ],
    'gateway'            => [],
];
