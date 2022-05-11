<?php
namespace Database\Factories\V1;

use App\Models\V1\Appliance;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ApplianceFactory extends Factory
{
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
    public function definition()
    {
        return [
            'appliance_uuid' => Str::uuid(),
            'appliance_name' => $this->faker->sentence(2),
            'appliance_logo_uri' => 'https://images.ukfast.co.uk/logos/wordpress/300x300_white.jpg',
            'appliance_description' => $this->faker->sentence(),
            'appliance_documentation_uri' => "https://en-gb.wordpress.org/",
            'appliance_publisher' => 'UKFast',
            'appliance_active' => 'Yes',
            'appliance_is_public' => 'Yes',
        ];
    }
}