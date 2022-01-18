<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\LoadBalancerNode;

$factory->define(LoadBalancerNode::class, function () {
    return [
        'load_balancer_id' => 'lb-aaaaaaaa',
        'instance_id' => 'i-aaaaaaaa',
        'node_id' => null,
    ];
});