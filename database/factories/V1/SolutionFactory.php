<?php

$factory->define(App\Models\V1\Solution::class, function (Faker\Generator $faker) {
    return [
        'ucs_reseller_reseller_id' => 1,
        'ucs_reseller_datacentre_id' => 1,
        'ucs_reseller_solution_name' => $faker->catchPhrase,
        'ucs_reseller_status' => 'Completed',
        'ucs_reseller_active' => 'Yes',
    ];
});