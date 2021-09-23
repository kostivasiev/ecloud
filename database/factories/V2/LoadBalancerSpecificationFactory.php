<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

$factory->define(\App\Models\V2\LoadBalancerSpecification::class, function () {
    return [
        'id' => 'lbs-0c03049b-dev',
        'name' => 'small',
        'node_count' => 1,
        'cpu' => 1,
        'ram' => 2,
        'hdd' => 20,
        'iops' => 300,
        'image_id' => 'img-aaaaaaaa',
    ];
});
