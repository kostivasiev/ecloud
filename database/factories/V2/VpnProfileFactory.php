<?php
namespace Database\Factories\V2;

use App\Models\V2\VpnProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class VpnProfileFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = VpnProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => 'VPN Profile',
        ];
    }
}
