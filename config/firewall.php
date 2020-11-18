<?php

return [
    'policies' => [
        [
            'name' => 'Infrastructure',
            'sequence' => 0,
            'rules' => [
                [
                    'name' => 'Ping',
                    'action' => 'ALLOW',
                    'sequence' => 0,
                    'direction' => 'OUT',
                    'enabled' => true,
                    'ports' => [
                        [
                            'protocol' => 'ICMPv4'
                        ]
                    ]
                ],
                [
                    'name' => 'DNS',
                    'action' => 'ALLOW',
                    'sequence' => 0,
                    'direction' => 'OUT',
                    'enabled' => true,
                    'ports' => [
                        [
                            'protocol' => 'UDP',
                            'destination' => 53
                        ],
                        [
                            'protocol' => 'TCP',
                            'destination' => 53
                        ]
                    ]
                ],
                [
                    'name' => 'NTP',
                    'action' => 'ALLOW',
                    'sequence' => 0,
                    'direction' => 'OUT',
                    'enabled' => true,
                    'ports' => [
                        [
                            'protocol' => 'UDP',
                            'destination' => 123
                        ],
                        [
                            'protocol' => 'TCP',
                            'destination' => 123
                        ]
                    ]
                ],
                [
                    'name' => 'HTTP/S',
                    'action' => 'ALLOW',
                    'sequence' => 0,
                    'direction' => 'OUT',
                    'enabled' => true,
                    'ports' => [
                        [
                            'protocol' => 'TCP',
                            'destination' => '80,443'
                        ]
                    ]
                ],
            ]
        ],
        [
            'name' => 'Remote Access',
            'sequence' => 0,
            'rules' => [
                [
                    'name' => 'RDP',
                    'action' => 'ALLOW',
                    'sequence' => 0,
                    'direction' => 'IN',
                    'enabled' => true,
                    'ports' => [
                        [
                            'protocol' => 'TCP',
                            'destination' => 3389
                        ]
                    ]
                ],
                [
                    'name' => 'SSH',
                    'action' => 'ALLOW',
                    'sequence' => 0,
                    'direction' => 'IN',
                    'enabled' => true,
                    'ports' => [
                        [
                            'protocol' => 'TCP',
                            'destination' => 2020
                        ]
                    ]
                ],

            ]
        ],
        [
            'name' => 'Web Services',
            'sequence' => 0,
            'rules' => [
                [
                    'name' => 'HTTP/S',
                    'action' => 'ALLOW',
                    'sequence' => 0,
                    'direction' => 'IN',
                    'enabled' => true,
                    'ports' => [
                        [
                            'protocol' => 'TCP',
                            'destination' => '80,443'
                        ]
                    ]
                ],
            ]
        ]
    ]
];
