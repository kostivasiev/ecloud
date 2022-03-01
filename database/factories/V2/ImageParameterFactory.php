<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\V2\ImageParameter;

$factory->define(ImageParameter::class, function () {
    return [
        'name' => 'Test Image Parameter',
        'key' => 'Username',
        'type' => 'String',
        'description' => 'Lorem ipsum',
        'required' => true,
        'validation_rule' => '/\w+/',
    ];
});
