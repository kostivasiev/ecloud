<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\ApplianceVersion;
use Faker\Generator as Faker;

$factory->define(ApplianceVersion::class, function (Faker $faker) {
    return [
        'appliance_version_version' => 1,
        'appliance_version_script_template' => '',
        'appliance_version_vm_template' => 'centos7-wordpress-v1.0.0',
    ];
});