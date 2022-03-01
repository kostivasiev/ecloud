<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\ApplianceVersionData;

$factory->define(ApplianceVersionData::class, function () {
    return [
        'key' => 'key',
        'value' => 'value',
    ];
});