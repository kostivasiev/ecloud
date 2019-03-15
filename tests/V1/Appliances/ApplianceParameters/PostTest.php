<?php

namespace Tests\Appliances\ApplianceParameters;

use Laravel\Lumen\Testing\DatabaseMigrations;

use App\Models\V1\ApplianceVersion;

use Tests\ApplianceTestCase;

class PostTest extends ApplianceTestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testCreateApplianceVersionParameter()
    {
        $applianceVersion = $this->appliances[0]->getLatestVersion();

        // Assert record does not exist
        $this->missingFromDatabase(
            'appliance_script_parameters',
            [
                'appliance_script_parametsrs_key' => 'test'
            ],
            env('DB_ECLOUD_CONNECTION')
        );

        // Create the appliance record
        $this->json('POST', '/v1/appliance-parameters', [
            'version_id' => $applianceVersion->uuid,
            'name' => 'Test param',
            'type' => 'String',
            'key' => 'test',
            'description' => 'This is a test parameter',
            'required' => false

        ], $this->validWriteHeaders)->seeStatusCode(201);
    }

    public function testCreateParameterRequiredButNotInScript()
    {
        $applianceVersion = $this->appliances[0]->getLatestVersion();

        // Assert record does not exist
        $this->missingFromDatabase(
            'appliance_script_parameters',
            [
                'appliance_script_parametsrs_key' => 'test'
            ],
            env('DB_ECLOUD_CONNECTION')
        );

        // Create the appliance record
        $this->json('POST', '/v1/appliance-parameters', [
            'version_id' => $applianceVersion->uuid,
            'name' => 'Test param',
            'type' => 'String',
            'key' => 'test',
            'description' => 'This is a test parameter',
            'required' => true

        ], $this->validWriteHeaders)
        ->seeStatusCode(400)
            ->seeJson(
                [
                    'title' => 'Bad Request',
                    'status' => 400
                ]
            );
    }


    public function testCreateApplianceVersionUnauthorised()
    {
        $applianceVersion = $this->appliances[0]->getLatestVersion();

        // Assert record does not exist
        $this->missingFromDatabase(
            'appliance_script_parameters',
            [
                'appliance_script_parametsrs_key' => 'test'
            ],
            env('DB_ECLOUD_CONNECTION')
        );

        // Create the appliance record
        $this->json('POST', '/v1/appliance-parameters', [
            'version_id' => $applianceVersion->uuid,
            'name' => 'Test param',
            'type' => 'String',
            'key' => 'test',
            'description' => 'This is a test parameter',
            'required' => false

        ], $this->validReadHeaders)->seeStatusCode(403);
    }
}