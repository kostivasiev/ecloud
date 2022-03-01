<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\ApplianceScriptParameters;
use Faker\Generator as Faker;

$factory->define(ApplianceScriptParameters::class, function (Faker $faker) {
    return [
        'appliance_script_parameters_appliance_version_id' => $faker->randomDigit,
        'appliance_script_parameters_name' => 'Random Parameter Name',
        'appliance_script_parameters_key' => 'random_key_name',
        'appliance_script_parameters_type' => 'String',
        'appliance_script_parameters_description' => 'Factory generated random parameter',
        'appliance_script_parameters_required' => 'Yes',
        'appliance_script_parameters_validation_rule' => null,
    ];
});
