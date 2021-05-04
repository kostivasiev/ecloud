<?php

return [
    'default_rules' => [
        [
            'DHCP_Ingress' => [
                'action' => 'ALLOW',
                'display_name' => 'DHCP_Ingress',
                'sequence_number' => 5001,
                'source_groups' => [
                    '10.0.0.2'
                ],
                'destination_groups' => [
                    "ANY"
                ],
                'services' => [
                    '/infra/services/DHCP-Client',
                    '/infra/services/DHCP-Server'
                ],
                'service_entries' => [],
                'profiles' => [
                    'ANY'
                ],
                'logged' => false,
                'scope' => [
                    '/infra/domains/default/groups/' . $this->networkPolicy->id
                ],
                'ip_protocol' => 'IPV4_IPV6'
            ],
            'DHCP_Egress' => [

            ]
        ]
    ]
];