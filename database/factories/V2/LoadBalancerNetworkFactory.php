<?php
namespace Database\Factories\V2;

use App\Models\V2\LoadBalancerNetwork;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoadBalancerNetworkFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = LoadBalancerNetwork::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [];
    }
}
