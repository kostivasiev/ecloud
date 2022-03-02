<?php
namespace Database\Factories\V2;

use App\Models\V2\Network;
use Illuminate\Database\Eloquent\Factories\Factory;

class NetworkFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Network::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => 'My Gateway 1',
            'router_id' => 'rtr-62827a58',
        ];
    }
}
