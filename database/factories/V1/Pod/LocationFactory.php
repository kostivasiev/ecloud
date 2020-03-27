<?php
$factory->define(App\Models\V1\Pod\Location::class, function (Faker\Generator $faker) {
    return [
        'ucs_datacentre_location_id' => 1,
        'ucs_datacentre_location_datacentre_id' => 1,
        'ucs_datacentre_location_name' => 'name',
    ];
});
