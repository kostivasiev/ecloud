<?php

namespace Tests\Appliances\AppplianceVersions;

use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\ApplianceTestCase;


class DeleteTest extends ApplianceTestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Deleting an appliance version should soft delete the appliance version
     * record and also soft delete al the appliance version parameters records.
     */
    public function testDeleteApplianceVersion()
    {
        $applianceVersion = $this->appliances[0]->getLatestVersion();
        $parameters = $applianceVersion->parameters;

        // get the parameters for the appliance version and check that they are marked as deleted
        foreach ($parameters as $parameter) {
            $this->assertNull($parameter->deleted_at);
        }

        $this->assertNull($applianceVersion->deleted_at);

        $this->json('DELETE', '/v1/appliance-versions/' . $applianceVersion->uuid , [], $this->validWriteHeaders);

        $this->assertResponseStatus(204);

        $applianceVersion->refresh();

        $this->assertNotNull($applianceVersion->deleted_at);

        foreach ($parameters as $parameter) {
            $parameter->refresh();
            $this->assertNotNull($parameter->deleted_at);
        }
    }

    public function testDeleteApplianceVersionUnauthorised()
    {
        $applianceVersion = $this->appliances[0]->getLatestVersion();

        $this->json('DELETE', '/v1/appliance-versions/' . $applianceVersion->uuid , [], $this->validReadHeaders);

        $this->assertResponseStatus(403);
    }
}