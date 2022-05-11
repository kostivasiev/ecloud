<?php
namespace Database\Factories\V2;

use App\Models\V2\LoadBalancer;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoadBalancerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = LoadBalancer::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => 'Load Balancer Cluster 1',
            'load_balancer_spec_id' => 'lbs-aaaaaaaa',
            'config_id' => '77898345',
        ];
    }
}
