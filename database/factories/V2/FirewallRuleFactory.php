<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\FirewallRule;
use Faker\Generator as Faker;

$factory->define(FirewallRule::class, function (Faker $faker) {
    return [
        'name' => $faker->domainWord,
        'sequence' => 10,
        'source' => '192.168.100.1',
        'destination' => '212.22.18.10',
        'action' => 'ALLOW',
        'direction' => 'IN',
        'enabled' => true
    ];
});
