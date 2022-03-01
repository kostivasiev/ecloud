<?php

$factory->define(App\Models\V1\Pod::class, function (Faker\Generator $faker) {
    return [
        'ucs_datacentre_public_name' => 'Test Pod ' . $faker->numberBetween(1, 5),
        'ucs_datacentre_active' => 'Yes',
        'ucs_datacentre_api_enabled' => 'Yes',
        'ucs_datacentre_public_enabled' => 'No',
        'ucs_datacentre_ucs_api_url' => 'http://localhost',
        'ucs_datacentre_vmware_api_url' => 'http://localhost'
    ];
});
