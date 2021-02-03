<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\Network;

$factory->define(Network::class, function () {
    return [
        'name' => 'My Gateway 1',
        'router_id' => 'rtr-62827a58',
    ];
});
