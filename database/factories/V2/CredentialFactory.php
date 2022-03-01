<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\Credential;

$factory->define(Credential::class, function () {
    return [
        'resource_id' => 'abc-abc132',
        'host' => 'https://127.0.0.1',
        'username' => 'someuser',
        'password' => 'somepassword',
        'port' => 8080
    ];
});
