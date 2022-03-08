<?php
namespace Database\Factories\V2;

use App\Models\V2\RouterThroughput;
use Illuminate\Database\Eloquent\Factories\Factory;

class RouterThroughputFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = RouterThroughput::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => '10Gb',
            'availability_zone_id' => 'az-aaaaaaaa',
            "committed_bandwidth" => 10240,
        ];
    }
}
