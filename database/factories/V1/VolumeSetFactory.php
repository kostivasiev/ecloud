<?php

$factory->define(\App\Models\V1\VolumeSet::class, function (Faker\Generator $faker) {
    return [
        'uuid' => $faker->uuid(),
        'name' => $faker->name(),
        'ucs_reseller_id' => 1,
        'max_iops' => 500
    ];
});
