<?php
namespace Database\Factories\V1;

use App\Models\V1\Trigger;
use Illuminate\Database\Eloquent\Factories\Factory;

class TriggerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Trigger::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'trigger_reseller_id' => 1,
            'trigger_description' => '1 x billable item',
            'trigger_reference_id' => $this->faker->randomNumber(),
            'trigger_reference_name' => 'server',
        ];
    }
}
