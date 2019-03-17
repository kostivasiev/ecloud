<?php

/**
 * Factory for Appliance Version Parameters
 */

use Ramsey\Uuid\Uuid;

$factory->define(App\Models\V1\ApplianceParameter::class, function (Faker\Generator $faker) {

    $data = [
        'appliance_script_parameters_uuid' => Uuid::uuid4()->toString(),
        'appliance_script_parameters_name' => $faker->sentence(2),
        'appliance_script_parameters_key' => str_replace(' ', '_', $faker->word(2)),
        'appliance_script_parameters_type' => 'String',
        'appliance_script_parameters_description' => $faker->sentence(8),
        'appliance_script_parameters_required' => 'Yes',
    ];

    return $data;
});
