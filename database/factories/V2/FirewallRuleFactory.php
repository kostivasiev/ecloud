<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\FirewallRule;
use Faker\Generator as Faker;

$factory->define(FirewallRule::class, function (Faker $faker) {
    return [
        'name' => $faker->domainWord,
    ];
});
