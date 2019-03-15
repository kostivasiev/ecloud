<?php

namespace Tests\Appliances\ApplianceVersions;

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

    public function testCreateApplianceVersion()
    {
        // Generate test appliance record
        $applianceVersion = factory(ApplianceVersion::class, 1)->make()->first();

        // Assert record does not exist
        $this->missingFromDatabase(
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
            'parameters'=> [
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
        $data = json_decode($res->response->getContent());

        $uuid = $data->data->id;

        // Check that the appliance was created
        $this->assertResponseStatus(201) && $this->seeInDatabase('appliance_version',
            [
                'appliance_version_uuid' => $uuid
            ]
        );
    }


    public function testCreateApplianceVersionRequiredParamMissingFromScript()
    {
        // Generate test appliance record
        $applianceVersion = factory(ApplianceVersion::class, 1)->make()->first();

        // Assert record does not exist
        $this->missingFromDatabase(
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
            'parameters'=> [
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
            ->seeStatusCode(400)
            ->seeJson([
                    'title' => 'Bad Request',
                    'detail' => "Required parameter 'MySQL Wordpress user password' with key 'THIS_REQUIRED_KEY_IS_MISSING_FROM_THE_SCRIPT' was not found in script template",
                    'status' => 400
        ]);
    }

    public function testCreateApplianceVersionUnauthorised()
    {
        // Generate test appliance record
        $applianceVersion = factory(ApplianceVersion::class, 1)->make()->first();

        // Create the appliance record
        $this->json('POST', '/v1/appliance-versions', [
            'appliance_id' => $this->appliances[0]->uuid,
            'version' => 4,
            'script_template' => $applianceVersion->script_template,
            'vm_template' => $applianceVersion->vm_template,
            'active' => true
        ], $this->validReadHeaders)
            ->assertResponseStatus(403);
    }
}
