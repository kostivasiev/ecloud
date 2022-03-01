<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

$factory->define(\App\Models\V2\AvailabilityZoneCapacity::class, function () {
    return [
        'type' => 'floating_ip',
        'current' => 0,
        'alert_warning' => 60,
        'alert_critical' => 80,
        'max' => 95,
    ];
});
