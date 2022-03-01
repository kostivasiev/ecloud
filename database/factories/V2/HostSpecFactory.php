<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\HostSpec;

$factory->define(HostSpec::class, function () {
    return [
        'cpu_sockets' => 2,
        'cpu_type' => 'E5-2643 v3',
        'cpu_cores' => 6,
        'cpu_clock_speed' => 4000,
        'ram_capacity' => 64,
        'name' => 'test-host-spec',
        'ucs_specification_name' => 'test-host-spec',
    ];
});