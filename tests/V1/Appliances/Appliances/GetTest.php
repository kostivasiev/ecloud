<?php

namespace Tests\Appliances\Appliances;

use Laravel\Lumen\Testing\DatabaseMigrations;

use Ramsey\Uuid\Uuid;

use Tests\ApplianceTestCase;

class GetTest extends ApplianceTestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test GET Appliance collection
     */
    public function testValidCollection()
    {
        $this->get('/v1/appliances', $this->validReadHeaders);

        $this->assertResponseStatus(200) && $this->seeJson([
            'total' => count($this->appliances),
        ]);
    }

    /**
     * Test GET Appliance Item
     */
    public function testValidItem()
    {
        $uuid = $this->appliances->first()->uuid;

        $this->get('/v1/appliances/' . $uuid, $this->validReadHeaders);

        $this->assertResponseStatus(200);
    }

    /**
     * Test GET Appliance - Invalid item
     * @throws \Exception
     */
    public function testInvalidItem()
    {
        $this->get('/v1/appliances/' . Uuid::uuid4()->toString(), $this->validReadHeaders);

        $this->assertResponseStatus(404);
    }

    /**
     * Test GET Appliance
     */
    public function testInvalidUuid()
    {
        $this->get('/v1/appliances/abc', $this->validReadHeaders);

        $this->assertResponseStatus(422);
    }

    /**
     * Test GET Appliance parameters
     */
    public function testApplianceParameters()
    {
        $appliance = $this->appliances[0];
        $parameters = $appliance->getLatestVersion()->parameters;

        $this->json('GET', '/v1/appliances/' . $appliance->uuid . '/parameters', [], $this->validWriteHeaders)
        ->seeStatusCode(200)
        ->seeJson([
                'id' => $parameters[0]->uuid,
                'version_id' => $parameters[0]->appliance_version_uuid,
                'name' => $parameters[0]->name,
                'key' => $parameters[0]->key,
                'type' => $parameters[0]->type,
                'description' => $parameters[0]->description,
                'required' => ($parameters[0]->required === 'Yes'),
                'validation_rule' => $parameters[0]->validation_rule
        ]);
    }


    /**
     * Test get appliance versions
     */
    public function testApplianceVersions()
    {
        $appliance = $this->appliances[0];
        $version = $appliance->versions[0];

        $this->json('GET', '/v1/appliances/' . $appliance->uuid . '/versions', [], $this->validWriteHeaders)
            ->seeStatusCode(200)
            ->seeJson([
                'id' => $version->uuid,
                'appliance_id' => $version->appliance_uuid,
                'version' => (int) $version->version,
                'script_template' => $version->script_template,
                'vm_template' => $version->vm_template,
                'active' => ($version->active == 'Yes')
            ]);
    }


    /**
     * Test get appliance latest versions
     */
    public function testApplianceVersion()
    {
        $appliance = $this->appliances[0];
        $version = $appliance->getLatestVersion();

        $this->json('GET', '/v1/appliances/' . $appliance->uuid . '/version', [], $this->validWriteHeaders)
            ->seeStatusCode(200)
            ->seeJson([
                'id' => $version->uuid,
                'appliance_id' => $version->appliance_uuid,
                'version' => (int) $version->version,
                'script_template' => $version->script_template,
                'vm_template' => $version->vm_template,
                'active' => ($version->active == 'Yes')
            ]);
    }


}
