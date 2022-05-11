<?php

namespace Database\Factories\V2;

use App\Models\V2\ImageParameter;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImageParameterFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ImageParameter::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'name' => 'Test Image Parameter',
            'key' => 'Username',
            'type' => 'String',
            'description' => 'Lorem ipsum',
            'required' => true,
            'validation_rule' => '/\w+/',
        ];
    }
}
