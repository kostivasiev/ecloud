<?php
namespace Database\Factories\V1\Pod;

use App\Models\V1\Pod\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Location::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'ucs_datacentre_location_id' => 1,
            'ucs_datacentre_location_datacentre_id' => 1,
            'ucs_datacentre_location_name' => 'name',
        ];
    }
}
