<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\LocalEndpoint;

$factory->define(LocalEndpoint::class, function () {
    return [
        'name' => 'Endpoint Name',
    ];
});