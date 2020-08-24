<?php

return [
    'availability_zones' => [],
    'vpc'                => [],

    'network'            => [
        'dhcp_ranges'        => '10.0.0.0/24',
        'lease_time'     => 604800,
        'dns_servers'    => [
            'primary'   => '81.201.138.244',
            'secondary' => '94.229.163.244',
        ],

        'server_address' => '10.0.0.2',
        'gateway_address' => '10.0.0.1',
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
