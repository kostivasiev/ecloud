<?php
namespace Database\Factories\V2;

use App\Models\V2\VpnProfileGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class VpnProfileGroupFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = VpnProfileGroup::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => 'Test Profile Group',
            'description' => 'Profile group description',
            'ike_profile_id' => 'nsx-default-l3vpn-ike-profile',
            'ipsec_profile_id' => 'nsx-default-l3vpn-tunnel-profile',
        ];
    }
}
