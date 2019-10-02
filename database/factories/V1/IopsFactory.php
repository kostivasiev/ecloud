<?php

$factory->define(\App\Models\V1\IopsTier::class, function (Faker\Generator $faker) {
    return [
        'uuid' => $faker->uuid(),
        'name' => $faker->name(),
        'max_iops' => 500
    ];
});
