<?php
namespace Database\Factories\V1;

use App\Models\V1\Pod;
use Illuminate\Database\Eloquent\Factories\Factory;

class PodFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Pod::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'ucs_datacentre_public_name' => 'Test Pod ' . $this->faker->numberBetween(1, 5),
            'ucs_datacentre_active' => 'Yes',
            'ucs_datacentre_api_enabled' => 'Yes',
            'ucs_datacentre_public_enabled' => 'No',
            'ucs_datacentre_ucs_api_url' => 'http://localhost',
            'ucs_datacentre_vmware_api_url' => 'http://localhost'
        ];
    }
}
