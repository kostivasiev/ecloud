<?php
namespace Database\Factories\V1\Appliance\Version;

use App\Models\V1\Appliance\Version\Data as DataModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class DataFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DataModel::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'key' => $this->faker->word(),
            'value' => $this->faker->sentence(),
        ];
    }
}
