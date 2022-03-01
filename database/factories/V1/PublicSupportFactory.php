<?php

use Ramsey\Uuid\Uuid;

$factory->define(App\Models\V1\PublicSupport::class, function (Faker\Generator $faker) {
    return [
        'id' => Uuid::uuid4()->toString(),
        'reseller_id' => 1,
    ];
});
