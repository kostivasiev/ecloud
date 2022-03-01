<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\AvailabilityZone;

$factory->define(AvailabilityZone::class, function () {
    return [
        'code' => 'LON1',
        'name' => 'London Zone 1',
        'datacentre_site_id' => 1,
        'san_name' => 'MCS-E-G0-3PAR-01',
        'ucs_compute_name' => 'GC-UCS-FI2-DEV-A',
        'is_public' => true,
    ];
});
