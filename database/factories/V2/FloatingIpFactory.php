<?php

namespace Database\Factories\V2;

use App\Models\V2\FloatingIp;
use Illuminate\Database\Eloquent\Factories\Factory;

class FloatingIpFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FloatingIp::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'ip_address' => '1.1.1.1'
        ];
    }
}
