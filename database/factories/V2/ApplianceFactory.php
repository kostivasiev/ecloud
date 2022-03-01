<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\Appliance;

$factory->define(Appliance::class, function () {
    return [
        'appliance_logo_uri' => 'https://localhost/logo.jpg',
        'appliance_description' => 'factory generated description',
        'appliance_documentation_uri' => 'https://loaclhost/docs',
        'appliance_publisher' => 'PHP Unit Tests',
    ];
});
