<?php

$factory->define(App\Models\V1\San::class, function (Faker\Generator $faker) {
    return [
        'servers_id' => 1,
        'servers_type' => 'san',
        'servers_reseller_id' => 1,
        'servers_friendly_name' => $faker->word(),
        'servers_hostname' => $faker->word(),
        'servers_subtype_id' => '',
        'servers_ecloud_ucs_reseller_id' => ''
    ];
});
