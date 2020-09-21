<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\Credential;
use Faker\Generator as Faker;

$factory->define(Credential::class, function (Faker $faker) {
    return [
        'resource_id' => 'abc-abc132',
        'host' => 'https://127.0.0.1',
        'user' => 'someuser',
        'password' => 'somepassword',
        'port' => 8080
    ];
});
