<?php

namespace Tests\V1\Appliances\ApplianceParameters;

use App\Models\V1\ApplianceParameter;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\V1\ApplianceTestCase;

class PatchTest extends ApplianceTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test updating an Appliance parameter
     * @return void
     */
    public function testUpdateApplianceParameter()
    {
        // Generate a test parameter
        $newParameter = factory(ApplianceParameter::class, 1)->make()->first();

        // Get an existing parameter
        $param = ApplianceParameter::query()->first();

        $this->assertDatabaseMissing(
            'appliance_script_parameters',
            [
                'appliance_script_parameters_name' => $newParameter->name,
                'appliance_script_parameters_key' => $newParameter->key,
                'appliance_script_parameters_type' => $newParameter->type,
                'appliance_script_parameters_description' => $newParameter->description,
                'appliance_script_parameters_required' => ($newParameter->required == 'Yes'),
            ],
            env('DB_ECLOUD_CONNECTION')
        );

        $this->json('PATCH', '/v1/appliance-parameters/' . $param->uuid, [
            'name' => $newParameter->name,
            'key' => $newParameter->key,
            'type' => $newParameter->type,
            'description' => $newParameter->description,
            'required' => 0,
            'validation_rule' => '/\w+/'
        ], $this->validWriteHeaders);

        $this->assertResponseStatus(204);

        $this->seeInDatabase(
            'appliance_script_parameters',
            [
                'appliance_script_parameters_uuid' => $param->uuid,
                'appliance_script_parameters_name' => $newParameter->name,
                'appliance_script_parameters_key' => $newParameter->key,
                'appliance_script_parameters_type' => $newParameter->type,
                'appliance_script_parameters_description' => $newParameter->description,
                'appliance_script_parameters_required' => 'No',
                'appliance_script_parameters_validation_rule' => '/\w+/'
            ],
            env('DB_ECLOUD_CONNECTION')
        );
    }

    /**
     * Test update an appliance version (non-admin)
     */
    public function testUpdateApplianceParameterNotAdmin()
    {
        // Generate a test parameter
        $newParameter = factory(ApplianceParameter::class, 1)->make()->first();

        // Get an existing parameter
        $param = ApplianceParameter::query()->first();

        $this->json('PATCH', '/v1/appliance-parameters/' . $param->uuid, [
            'name' => $newParameter->name
        ], $this->validReadHeaders)
            ->assertStatus(401);
    }
}
