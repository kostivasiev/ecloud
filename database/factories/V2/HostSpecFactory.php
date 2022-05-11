<?php

namespace Database\Factories\V2;

use App\Models\V2\HostSpec;
use Illuminate\Database\Eloquent\Factories\Factory;

class HostSpecFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = HostSpec::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'cpu_sockets' => 2,
            'cpu_type' => 'E5-2643 v3',
            'cpu_cores' => 6,
            'cpu_clock_speed' => 4000,
            'ram_capacity' => 64,
            'name' => 'test-host-spec',
            'ucs_specification_name' => 'test-host-spec',
        ];
    }
}
