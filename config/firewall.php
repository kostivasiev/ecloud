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
                    'source' => 'ANY',
                    'destination' => 'ANY',
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
                    'source' => 'ANY',
                    'destination' => 'ANY',
                    'ports' => [
                        [
                            'protocol' => 'UDP',
                            'source' => 'ANY',
                            'destination' => 53
                        ],
                        [
                            'protocol' => 'TCP',
                            'source' => 'ANY',
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
                    'source' => 'ANY',
                    'destination' => 'ANY',
                    'ports' => [
                        [
                            'protocol' => 'UDP',
                            'source' => 'ANY',
                            'destination' => 123
                        ],
                        [
                            'protocol' => 'TCP',
                            'source' => 'ANY',
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
                    'source' => 'ANY',
                    'destination' => 'ANY',
                    'ports' => [
                        [
                            'protocol' => 'TCP',
                            'source' => 'ANY',
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
                    'source' => 'ANY',
                    'destination' => 'ANY',
                    'ports' => [
                        [
                            'protocol' => 'TCP',
                            'source' => 'ANY',
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
                    'source' => 'ANY',
                    'destination' => 'ANY',
                    'ports' => [
                        [
                            'protocol' => 'TCP',
                            'source' => 'ANY',
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
                    'source' => 'ANY',
                    'destination' => 'ANY',
                    'ports' => [
                        [
                            'protocol' => 'TCP',
                            'source' => 'ANY',
                            'destination' => '80,443'
                        ]
                    ]
                ],
            ]
        ]
    ]
];
