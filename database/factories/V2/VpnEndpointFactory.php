<?php
namespace Database\Factories\V2;

use App\Models\V2\VpnEndpoint;
use Illuminate\Database\Eloquent\Factories\Factory;

class VpnEndpointFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = VpnEndpoint::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => 'Endpoint Name',
        ];
    }
}
