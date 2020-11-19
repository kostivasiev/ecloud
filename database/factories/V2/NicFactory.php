<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\Nic;
use Faker\Generator as Faker;

$factory->define(Nic::class, function (Faker $faker) {
    return [
        'mac_address' => $faker->macAddress,
        'instance_id' => 'i-' . bin2hex(random_bytes(4)),
        'network_id' => 'net-' . bin2hex(random_bytes(4)),
        'ip_address' => $faker->ipv4,
    ];
});
