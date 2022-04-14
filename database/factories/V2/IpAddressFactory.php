<?php
namespace Database\Factories\V2;

use App\Models\V2\IpAddress;
use App\Models\V2\Network;
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
            'ip_address' => $this->faker->ipv4(),
            'name' => 'test IP',
             // Not yet supported until we convert other resources to the new laravel model format
             //'network_id' => Network::where('id','net-test')->firstOr(fn() => Network::factory(['id' => 'net-test'])->create())->id,
            'type' => 'normal'
        ];
    }
}
