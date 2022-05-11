<?php
namespace Database\Factories\V1;

use App\Models\V1\San;
use Illuminate\Database\Eloquent\Factories\Factory;

class SanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = San::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'servers_id' => 1,
            'servers_type' => 'san',
            'servers_reseller_id' => 1,
            'servers_friendly_name' => $this->faker->word(),
            'servers_hostname' => $this->faker->word(),
            'servers_subtype_id' => '',
            'servers_ecloud_ucs_reseller_id' => '',
        ];
    }
}
