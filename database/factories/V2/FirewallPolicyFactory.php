<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\FirewallPolicy;
use Faker\Generator as Faker;

$factory->define(FirewallPolicy::class, function (Faker $faker) {
    return [
        'name' => $faker->domainWord,
        'sequence' => 10,
    ];
});
