<?php

namespace Database\Factories\V2;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\V2\ApplianceScriptParameters;

class ApplianceScriptParametersFactory extends Factory
{
    protected $connection = 'ecloud';

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ApplianceScriptParameters::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'appliance_script_parameters_appliance_version_id' => $this->faker->randomDigit,
            'appliance_script_parameters_name' => 'Random Parameter Name',
            'appliance_script_parameters_key' => 'random_key_name',
            'appliance_script_parameters_type' => 'String',
            'appliance_script_parameters_description' => 'Factory generated random parameter',
            'appliance_script_parameters_required' => 'Yes',
            'appliance_script_parameters_validation_rule' => null,
        ];
    }
}
