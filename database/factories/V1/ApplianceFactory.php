<?php

/**
 * Factory for Appliance resource collection
 */

use Ramsey\Uuid\Uuid;

$factory->define(App\Models\V1\Appliance::class, function (Faker\Generator $faker) {
    $data = [
        'appliance_uuid' => Uuid::uuid4()->toString(),
        'appliance_name' => $faker->sentence(2),
        'appliance_logo_uri' => 'https://images.ukfast.co.uk/logos/wordpress/300x300_white.jpg',
        'appliance_description' => $faker->sentence(),
        'appliance_documentation_uri' => "https://en-gb.wordpress.org/",
        'appliance_publisher' => 'UKFast',
        'appliance_active' => 'Yes',
        'appliance_is_public' => 'Yes',
    ];

    return $data;
});
