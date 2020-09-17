<?php

return [
    'capacity' => [
        'min' => env('VOLUME_CAPACITY_MIN', 1),
        'max' => env('VOLUME_CAPACITY_MAX', 1000)
    ],
];
