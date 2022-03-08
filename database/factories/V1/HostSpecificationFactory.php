<?php
namespace Database\Factories\V1;

use App\Models\V1\HostSpecification;
use Illuminate\Database\Eloquent\Factories\Factory;

class HostSpecificationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = HostSpecification::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'ucs_specification_active' => 'Yes',
            'ucs_specification_name' => 'DUAL-E5-2680--128GB',
            'ucs_specification_friendly_name' => '2 x Oct Core 2.7Ghz (E5-2680 v1) 128GB',
            'ucs_specification_cpu_qty' => 2,
            'ucs_specification_cpu_cores' => 8,
            'ucs_specification_cpu_speed' => '2.7Ghz',
            'ucs_specification_ram' => '128GB',
        ];
    }
}
