<?php

return [
    'availability_zones' => [],
    'vpc'                => [],
    'network'            => [
        'range'        => '10.0.0.0/24',
        'type'           => 'local',
        'lease_time'     => 604800,
        'dns_servers'    => [
            'primary'   => '81.201.138.244',
            'secondary' => '94.229.163.244',
        ],
        'second_address' => '10.0.0.2',
        'gateway'        => '10.0.0.1',
    ],
    'dhcp'               => [
        'internal_address' => '192.168.0.0/20', // this value needs review/removal
        'lease_time'       => 604800,
        'profile'          => 'edge-cluster', // is this correct?
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
