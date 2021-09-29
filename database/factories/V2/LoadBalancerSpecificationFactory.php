<?php
namespace Database\Factories\V2;

use App\Models\V2\LoadBalancerSpecification;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoadBalancerSpecificationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = LoadBalancerSpecification::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'id' => 'lbs-0c03049b-dev',
            'name' => 'small',
            'node_count' => 1,
            'cpu' => 1,
            'ram' => 2,
            'hdd' => 20,
            'iops' => 300,
            'image_id' => 'img-aaaaaaaa',
        ];
    }
}