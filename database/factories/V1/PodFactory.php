<?php

$factory->define(App\Models\V1\Pod::class, function (Faker\Generator $faker) {
    return [
        'ucs_datacentre_public_name' => 'Test Pod ' . $faker->numberBetween(1, 5),
        'ucs_datacentre_active' => 'Yes',
        'ucs_datacentre_api_enabled' => 'Yes',
        'ucs_datacentre_vcl_server_id' => $faker->numberBetween(1,1000),
        'ucs_datacentre_vce_server_id' => $faker->numberBetween(1,1000),
    ];
});
