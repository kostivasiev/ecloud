<?php

/**
 * Factory for Virtual Machine (servers) resource collection
 */
$factory->define(App\Models\V1\VirtualMachine::class, function (Faker\Generator $faker) {

    $platforms = [
        'Windows',
        'Linux'
    ];

    $licenses = [
        'Linux' => [
            'CentOS6 x86_64',
            'CentOS7 x86_64'
        ],
        'Windows' => [
            ''
        ]
    ];

    $environments = [
        'Hybrid',
        'Public',
        'Private',
        'Burst'
    ];

    $platform = $faker->randomElement($platforms);
    $serverLicenseName = $faker->randomElement($licenses[$platform]);

    $data = [
        'servers_reseller_id' => 1,
        'servers_type' => 'ecloud vm',
        'servers_subtype_id' => 1,
        'servers_ecloud_ucs_reseller_id' => 1,
        'servers_friendly_name' => $faker->sentence(2),
        'servers_hostname' => '172.16.28.173.srvlist.ukfast.net',
        'servers_netnios_name' => '172.16.28.173.srvlist.ukfast.net',
        'servers_cpu' => rand(1, 5),
        'servers_memory' => rand(1, 5),
        'servers_hdd' => rand(20, 300),
        'servers_platform' => $platform,
        'servers_license' => $serverLicenseName,
        'servers_backup' => $faker->boolean,
        'servers_advanced_support' => $faker->boolean,
        'servers_status' => 'Complete',
        'servers_ecloud_type' => $faker->randomElement($environments),
    ];

    return $data;
});
