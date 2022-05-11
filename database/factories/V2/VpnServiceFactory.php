<?php
namespace Database\Factories\V2;

use App\Models\V2\VpnService;
use Illuminate\Database\Eloquent\Factories\Factory;

class VpnServiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = VpnService::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => 'Office VPN',
        ];
    }
}
