<?php

return [
    'profiles' => [
        'segment-security-profile' => 'ecloud-segment-security-profile',
        'spoofguard-profile' => 'ecloud-spoofguard-profile',
        'ip-discovery-profile' => 'ecloud-ip-discovery-profile',
        'mac-discovery-profile' => 'ecloud-mac-discovery-profile',
    ],
    'management_range' => [
        'standard' => '192.168.0.0/17',
        'advanced' => '192.168.128.0/17',
    ],
    'rule_templates' => [
        [
            'name' => 'Logic Monitor Collector',
            'action' => 'ALLOW',
            'sequence' => 0,
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
    ],
];
