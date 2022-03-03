<?php

namespace Tests\V1\Appliances\ApplianceVersions;

use App\Models\V1\ApplianceVersion;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\V1\ApplianceTestCase;

class PostTest extends ApplianceTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testCreateApplianceVersion()
    {
        // Generate test appliance record
        $applianceVersion = ApplianceVersion::factory(1)->make()->first();

        // Assert record does not exist
        $this->assertDatabaseMissing(
            'appliance_version',
            [
                'appliance_version_version' => 4
            ],
            env('DB_ECLOUD_CONNECTION')
        );

        // Create the appliance record
        $res = $this->json('POST', '/v1/appliance-versions', [
            'appliance_id' => $this->appliances[0]->uuid,
            'version' => 4,
            'script_template' => $applianceVersion->script_template,
            'vm_template' => $applianceVersion->vm_template,
            'os_license_id' => 123,
            'active' => true,
            'parameters' => [
                [
                    'name' => 'Wordpredd URL',
                    'type' => 'String',
                    'key' => 'wordpress_url',
                    'description' => 'Wordpress URL',
                    'required' => false
                ],
                [
                    'name' => 'MySQL Wordpress user password',
                    'type' => 'String',
                    'key' => 'mysql_wordpress_user_password',
                    'description' => 'Wordpress user password',
                    'required' => true
                ],
                [
                    'name' => 'MySQL root password',
                    'type' => 'String',
                    'key' => 'mysql_root_password',
                    'description' => 'The root password for the MySQL database',
                    'required' => true,
                    'validation_rule' => '/w+/'
                ]
            ]
        ], $this->validWriteHeaders);

        // Get the ID of the created record
        $data = json_decode($res->getContent());

        $uuid = $data->data->id;

        // Check that the appliance was created
        $res->assertStatus(201);

        $this->assertDatabaseHas(
            'appliance_version',
            [
                'appliance_version_uuid' => $uuid
            ],
            env('DB_ECLOUD_CONNECTION')
        );
    }


    public function testCreateApplianceVersionRequiredParamMissingFromScript()
    {
        // Generate test appliance record
        $applianceVersion = ApplianceVersion::factory(1)->make()->first();

        // Assert record does not exist
        $this->assertDatabaseMissing(
            'appliance_version',
            [
                'appliance_version_version' => 4
            ],
            env('DB_ECLOUD_CONNECTION')
        );

        // Create the appliance record
        $this->json('POST', '/v1/appliance-versions', [
            'appliance_id' => $this->appliances[0]->uuid,
            'version' => 4,
            'script_template' => $applianceVersion->script_template,
            'vm_template' => $applianceVersion->vm_template,
            'os_license_id' => 123,
            'active' => true,
            'parameters' => [
                [
                    'name' => 'Wordpredd URL',
                    'type' => 'String',
                    'key' => 'wordpress_url',
                    'description' => 'Wordpress URL',
                    'required' => false
                ],
                [
                    'name' => 'MySQL Wordpress user password',
                    'type' => 'String',
                    'key' => 'THIS_REQUIRED_KEY_IS_MISSING_FROM_THE_SCRIPT',
                    'description' => 'Wordpress user password',
                    'required' => true
                ],
                [
                    'name' => 'MySQL root password',
                    'type' => 'String',
                    'key' => 'mysql_root_password',
                    'description' => 'The root password for the MySQL database',
                    'required' => true,
                    'validation_rule' => '/w+/'
                ]
            ]
        ], $this->validWriteHeaders)
            ->assertStatus(400)
            ->seeJsonFragment([
                'title' => 'Bad Request',
                'detail' => "Required parameter 'MySQL Wordpress user password' with key 'THIS_REQUIRED_KEY_IS_MISSING_FROM_THE_SCRIPT' was not found in script template",
                'status' => 400
            ]);
    }

    public function testCreateApplianceVersionUnauthorised()
    {
        // Generate test appliance record
        $applianceVersion = ApplianceVersion::factory(1)->make()->first();

        // Create the appliance record
        $this->json('POST', '/v1/appliance-versions', [
            'appliance_id' => $this->appliances[0]->uuid,
            'version' => 4,
            'script_template' => $applianceVersion->script_template,
            'vm_template' => $applianceVersion->vm_template,
            'active' => true
        ], $this->validReadHeaders)
            ->assertStatus(401);
    }
}
