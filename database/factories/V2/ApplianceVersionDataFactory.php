<?php

namespace Database\Factories\V2;

use App\Models\V2\ApplianceVersionData;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApplianceVersionDataFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ApplianceVersionData::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'key' => 'key',
            'value' => 'value',
        ];
    }
}
