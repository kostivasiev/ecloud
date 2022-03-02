<?php
namespace Database\Factories\V2;

use App\Models\V2\Router;
use Illuminate\Database\Eloquent\Factories\Factory;

class RouterFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Router::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => 'Primary Router',
            'vpc_id' => 'vpc-a7d7c4e6',
            'router_throughput_id' => 'rtp-abc123'
        ];
    }
}
