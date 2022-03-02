<?php
namespace Database\Factories\V2;

use App\Models\V2\NetworkPolicy;
use Illuminate\Database\Eloquent\Factories\Factory;

class NetworkPolicyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = NetworkPolicy::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => 'np-test',
        ];
    }
}
