<?php

$factory->define(App\Models\V1\Tag::class, function (Faker\Generator $faker) {
    return [
        'metadata_key' => $faker->domainWord(),
        'metadata_value' => $faker->domainWord(),
        'metadata_created' => $faker->dateTime(),
        'metadata_reseller_id' => 1,
        'metadata_resource' => 'server',
        'metadata_resource_id' => 123,
        'metadata_createdby' => 'API Client',
        'metadata_createdby_id' => 1,
    ];
});
