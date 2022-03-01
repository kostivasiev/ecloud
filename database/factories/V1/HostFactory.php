<?php

$factory->define(App\Models\V1\Host::class, function (Faker\Generator $faker) {
    return [
        'ucs_node_reseller_id' => 1,
        'ucs_node_ucs_reseller_id' => 1,
        'ucs_node_datacentre_id' => 1,
        'ucs_node_specification_id' => 1,
        'ucs_node_status' => 'Complete',
        'ucs_node_location_id' => 1,
    ];
});
