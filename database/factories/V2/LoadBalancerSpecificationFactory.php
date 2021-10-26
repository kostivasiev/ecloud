<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\LoadBalancerSpecification;

$factory->define(LoadBalancerSpecification::class, function () {

        return [
            'name' => 'medium',
            'description' => 'HA load balancer, suitable for large sites with notable amounts of daily traffic.',
            'node_count' => 2,
            'cpu' => 1,
            'ram' => 2,
            'hdd' => 20,
            'iops' => 300,
            'image_id' => 'img-aaaaaaaa',
        ];
});