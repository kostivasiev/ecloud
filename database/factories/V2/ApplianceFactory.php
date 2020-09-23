<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\Appliance;
use Faker\Generator as Faker;

$factory->define(Appliance::class, function (Faker $faker) {
    return [
        'appliance_logo_uri' => $faker->url,
        'appliance_description' => 'factory generated description',
        'appliance_documentation_uri' => $faker->url,
        'appliance_publisher' => 'PHP Unit Tests',
    ];
});
