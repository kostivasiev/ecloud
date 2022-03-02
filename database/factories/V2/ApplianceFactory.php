<?php

namespace Database\Factories\V2;

use App\Models\V2\Appliance;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApplianceFactory extends Factory
{
    protected $connection = 'ecloud';

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Appliance::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'appliance_logo_uri' => 'https://localhost/logo.jpg',
            'appliance_description' => 'factory generated description',
            'appliance_documentation_uri' => 'https://loaclhost/docs',
            'appliance_publisher' => 'PHP Unit Tests',
        ];
    }
}
