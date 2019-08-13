<?php

$factory->define(App\Models\V1\ActiveDirectoryDomain::class, function (Faker\Generator $faker) {
    return [
        'ad_domain_reseller_id' => 1,
        'ad_domain_name' => $faker->domainName,
    ];
});
