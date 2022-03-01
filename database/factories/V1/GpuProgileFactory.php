<?php

$factory->define(App\Models\V1\GpuProfile::class, function (Faker\Generator $faker) {
    return [
        'uuid' => $faker->uuid(),
        'name' => $faker->word(),
        'profile_name' => 'grid_v100d-32q',
        'card_type' => 'v100'
    ];
});
