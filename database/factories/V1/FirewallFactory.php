<?php

$factory->define(App\Models\V1\Firewall::class, function (Faker\Generator $faker) {
    return [
        'servers_reseller_id' => 1,
        'servers_type' => 'virtual firewall',
        'servers_subtype_id' => 2,
        'servers_friendly_name' => $faker->company,
        'servers_hostname' => $faker->domainName,
        'servers_ip' => $faker->ipv4,
        'servers_active' => 'y',
        'servers_firewall_role' => 'Single',
        'servers_ecloud_ucs_reseller_id' => 1,
    ];
});
