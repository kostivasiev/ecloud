<?php

namespace Database\Factories\V2;

use App\Models\V2\AvailabilityZoneCapacity;
use Illuminate\Database\Eloquent\Factories\Factory;

class AvailabilityZoneCapacityFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AvailabilityZoneCapacity::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'type' => 'floating_ip',
            'current' => 0,
            'alert_warning' => 60,
            'alert_critical' => 80,
            'max' => 95,
        ];
    }
}
