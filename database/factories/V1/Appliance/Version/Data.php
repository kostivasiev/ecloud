<?php
$factory->define(App\Models\V1\Appliance\Version\Data::class, function (Faker\Generator $faker) {
    return [
        'key' => $faker->word(),
        'value' => $faker->sentence(),
    ];
});
