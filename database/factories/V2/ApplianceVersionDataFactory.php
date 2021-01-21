<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\ApplianceVersionData;
use Faker\Generator as Faker;

$factory->define(ApplianceVersionData::class, function (Faker $faker) {
    return [
        'key' => $faker->word,
        'value' => $faker->word,
    ];
});