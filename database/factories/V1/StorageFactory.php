<?php

$factory->define(App\Models\V1\Storage::class, function (Faker\Generator $faker) {
    return [
        'ucs_datacentre_id' => 1,
        'server_id' => 1,
        'qos_enabled' => 'No',
    ];
});
