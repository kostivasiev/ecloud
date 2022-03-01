<?php

$factory->define(App\Models\V1\Trigger::class, function (Faker\Generator $faker) {
    return [
        'trigger_reseller_id' => 1,
        'trigger_description' => '1 x billable item',
        'trigger_reference_id' => $faker->randomNumber(),
        'trigger_reference_name' => 'server',
    ];
});
