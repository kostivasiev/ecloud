<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\LoadBalancerCluster;
use Faker\Generator as Faker;

$factory->define(LoadBalancerCluster::class, function (Faker $faker) {
    return [
        'name' => 'Load Balancer Cluster 1',
        'nodes' => 3
    ];
});

