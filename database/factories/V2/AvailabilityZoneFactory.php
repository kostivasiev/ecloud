<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\AvailabilityZone;
use Faker\Generator as Faker;

$factory->define(AvailabilityZone::class, function (Faker $faker) {
    return [
        'code' => 'LON1',
        'name' => 'London Zone 1',
        'datacentre_site_id' => 1,
        'nsx_manager_endpoint' => 'https://www.ukfast.co.uk/',
        'nsx_edge_cluster_id' => '0000-0000-0000-0000-0000',
    ];
});
