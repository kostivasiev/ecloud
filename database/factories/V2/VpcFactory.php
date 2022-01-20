<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\Vpc;

$factory->define(Vpc::class, function () {
    return [
        'name' => 'Virtual Private Cloud Name',
        'reseller_id' => 1,
        'support_enabled' => false,
        'console_enabled' => false,
    ];
});
