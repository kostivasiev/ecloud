<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\LoadBalancer;

$factory->define(LoadBalancer::class, function () {
    return [
        'name' => 'Load Balancer Cluster 1',
        'load_balancer_spec_id' => 'lbs-aaaaaaaa',
        'config_id' => '77898345'
    ];
});

