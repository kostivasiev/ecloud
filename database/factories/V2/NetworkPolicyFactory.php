<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\NetworkPolicy;

$factory->define(NetworkPolicy::class, function () {
    return [
        'name' => 'name',
    ];
});