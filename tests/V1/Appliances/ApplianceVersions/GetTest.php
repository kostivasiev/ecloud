<?php

namespace Tests\V1\Appliances\ApplianceVersions;

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
     * Test GET appliance version collection
     */
    public function testValidCollection()
    {
        $this->get('/v1/appliance-versions', $this->validWriteHeaders);

        $this->assertResponseStatus(200) && $this->seeJson([
            'total' => 9,
        ]);
    }

    /**
     * Test GET Appliance Item
     */
    public function testValidItem()
    {
        $uuid = $this->appliances[0]->getLatestVersion()->uuid;

        $this->get('/v1/appliance-versions/' . $uuid, $this->validWriteHeaders);

        $this->assertResponseStatus(200);
    }

    /**
     * Test GET appliance version - Invalid item
     * @throws \Exception
     */
    public function testInvalidItem()
    {
        $this->get('/v1/appliance-versions/' . Uuid::uuid4()->toString(), $this->validWriteHeaders);

        $this->assertResponseStatus(404);
    }


    /**
     * Test GET Appliance version parameters
     */
    public function testVersionParameters()
    {
        $latestVersion = $this->appliances[0]->getLatestVersion();
        $parameters = $latestVersion->parameters;

        $this->json('GET', '/v1/appliance-versions/' . $latestVersion->uuid . '/parameters', [],
            $this->validWriteHeaders)
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
}
