<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\ImageMetadata;

$factory->define(ImageMetadata::class, function () {
    return [
        'key' => 'test.key',
        'value' => 'test.value',
    ];
});
