<?php
namespace Database\Factories\V2;

use App\Models\V2\NetworkRule;
use Illuminate\Database\Eloquent\Factories\Factory;

class NetworkRuleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = NetworkRule::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'sequence' => 1,
            'source' => '10.0.1.0/32',
            'destination' => '10.0.2.0/32',
            'action' => 'ALLOW',
            'enabled' => true,
            'direction' => 'IN_OUT',
        ];
    }
}
