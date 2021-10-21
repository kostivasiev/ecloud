<?php
namespace Database\Factories\V2;

use App\Models\V2\Vip;
use Illuminate\Database\Eloquent\Factories\Factory;

class VipFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Vip::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'id' => 'vip-aaaaaaaa-dev',
            'loadbalancer_id' => 'lbc-c11e4492-dev',
            'network_id' => 'net-aaaaaaaa',
            'name' => 'vip-aaaaaaaa-dev'
        ];
    }
}
