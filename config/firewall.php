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
                [
                    'name' => 'Windows Activation',
                    'action' => 'ALLOW',
                    'sequence' => 1,
                    'direction' => 'OUT',
                    'enabled' => true,
                    'source' => 'ANY',
                    'destination' => 'ANY',
                    'ports' => [
                        [
                            'protocol' => 'TCP',
                            'source' => 'ANY',
                            'destination' => '1688'
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
    ],
    'system' => [
        'name' => 'System',
        'sequence' => 0,
        'rules' => [
            [
                'name' => 'McAfee',
                'action' => 'ALLOW',
                'sequence' => 0,
                'direction' => 'OUT',
                'enabled' => true,
                'source' => 'ANY',
                'destination' => '94.229.162.0/27',
                'ports' => [
                    [
                        'protocol' => 'TCP',
                        'source' => 'ANY',
                        'destination' => '9967-9973'
                    ],
                    [
                        'protocol' => 'TCP',
                        'source' => 'ANY',
                        'destination' => 8801
                    ],
                    [
                        'protocol' => 'TCP',
                        'source' => 'ANY',
                        'destination' => 49159
                    ],
                    [
                        'protocol' => 'TCP',
                        'source' => 'ANY',
                        'destination' => 139
                    ],
                    [
                        'protocol' => 'UDP',
                        'source' => 'ANY',
                        'destination' => 137
                    ],
                ]
            ],
            [
                'name' => 'McAfee',
                'action' => 'ALLOW',
                'sequence' => 0,
                'direction' => 'IN',
                'enabled' => true,
                'source' => '94.229.162.0/27',
                'destination' => 'ANY',
                'ports' => [
                    [
                        'protocol' => 'TCP',
                        'source' => 'ANY',
                        'destination' => '9967-9973'
                    ],
                    [
                        'protocol' => 'TCP',
                        'source' => 'ANY',
                        'destination' => 8801
                    ],
                    [
                        'protocol' => 'TCP',
                        'source' => 'ANY',
                        'destination' => 49159
                    ],
                    [
                        'protocol' => 'TCP',
                        'source' => 'ANY',
                        'destination' => 139
                    ],
                    [
                        'protocol' => 'UDP',
                        'source' => 'ANY',
                        'destination' => 137
                    ],
                ]
            ],
            [
                'name' => 'Windows Update',
                'action' => 'ALLOW',
                'sequence' => 0,
                'direction' => 'OUT',
                'enabled' => true,
                'source' => 'ANY',
                'destination' => '185.182.91.220',
                'ports' => [
                    [
                        'protocol' => 'TCP',
                        'source' => 'ANY',
                        'destination' => 80
                    ],
                    [
                        'protocol' => 'TCP',
                        'source' => 'ANY',
                        'destination' => 443
                    ],
                ]
            ],
            [
                'name' => 'KMS',
                'action' => 'ALLOW',
                'sequence' => 0,
                'direction' => 'OUT',
                'enabled' => true,
                'source' => 'ANY',
                'destination' => '94.229.175.148',
                'ports' => [
                    [
                        'protocol' => 'TCP',
                        'source' => 'ANY',
                        'destination' => 1688
                    ],
                ]
            ],
            [
                'name' => 'WINRM',
                'action' => 'ALLOW',
                'sequence' => 0,
                'direction' => 'IN',
                'enabled' => true,
                'source' => '46.37.163.142,46.37.163.143',
                'destination' => 'ANY',
                'ports' => [
                    [
                        'protocol' => 'TCP',
                        'source' => 'ANY',
                        'destination' => 5986
                    ],
                ]
            ],
            [
                'name' => 'Support Access',
                'action' => 'ALLOW',
                'sequence' => 0,
                'direction' => 'IN',
                'enabled' => true,
                'source' => '80.244.179.100',
                'destination' => 'ANY',
                'ports' => [
                    [
                        'protocol' => 'TCP',
                        'source' => 'ANY',
                        'destination' => 3389
                    ],
                    [
                        'protocol' => 'TCP',
                        'source' => 'ANY',
                        'destination' => 2020
                    ],
                    [
                        'protocol' => 'TCP',
                        'source' => 'ANY',
                        'destination' => 22
                    ],
                    [
                        'protocol' => 'TCP',
                        'source' => 'ANY',
                        'destination' => 3399
                    ],
                ]
            ],
        ]
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
