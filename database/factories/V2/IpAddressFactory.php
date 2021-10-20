<?php
namespace Database\Factories\V2;

use App\Models\V2\IpAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

class IpAddressFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = IpAddress::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'ip_address' => '1.1.1.1',
            'network_id' => 'net-aaaaaaaa',
            'type' => 'normal',
            'name' => 'test IP',
        ];
    }
}
