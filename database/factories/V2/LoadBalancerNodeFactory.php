<?php
namespace Database\Factories\V2;

use App\Models\V2\LoadBalancerNode;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoadBalancerNodeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = LoadBalancerNode::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'load_balancer_id' => 'lb-aaaaaaaa',
            'instance_id' => 'i-aaaaaaaa',
            'node_id' => null,
        ];
    }
}
