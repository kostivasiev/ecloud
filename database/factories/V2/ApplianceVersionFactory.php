<?php

namespace Database\Factories\V2;

use App\Models\V2\ApplianceVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApplianceVersionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ApplianceVersion::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'appliance_version_version' => 1,
            'appliance_version_script_template' => '',
            'appliance_version_vm_template' => 'centos7-wordpress-v1.0.0',
        ];
    }
}
