<?php
namespace Database\Factories\V2;

use App\Models\V2\Nic;
use Illuminate\Database\Eloquent\Factories\Factory;

class NicFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Nic::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'mac_address' => '01-23-45-67-89-AB',
            'instance_id' => 'i-' . bin2hex(random_bytes(4)),
            'network_id' => 'net-' . bin2hex(random_bytes(4)),
        ];
    }
}
