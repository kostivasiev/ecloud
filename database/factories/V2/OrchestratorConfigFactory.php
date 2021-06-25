<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\OrchestratorConfig;

$factory->define(OrchestratorConfig::class, function () {
    return [
        'reseller_id' => 1,
        'employee_id' => 1,
        'data' => '{"foo":"bar"}'
    ];
});
