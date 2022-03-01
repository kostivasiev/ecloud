<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\FirewallPolicy;

$factory->define(FirewallPolicy::class, function () {
    return [
        'name' => 'name',
        'sequence' => 10,
    ];
});
