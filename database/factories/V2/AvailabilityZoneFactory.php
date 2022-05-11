<?php

namespace Database\Factories\V2;

use App\Models\V2\AvailabilityZone;
use Illuminate\Database\Eloquent\Factories\Factory;

class AvailabilityZoneFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AvailabilityZone::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'code' => 'LON1',
            'name' => 'London Zone 1',
            'datacentre_site_id' => 1,
            'san_name' => 'MCS-E-G0-3PAR-01',
            'ucs_compute_name' => 'GC-UCS-FI2-DEV-A',
            'is_public' => true,
        ];
    }
}
