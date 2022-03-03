<?php

namespace Tests\V1\Appliances\Appliances;

use Tests\V1\ApplianceTestCase;

class DeleteTest extends ApplianceTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Deleting an appliance should soft delete the appliance, any appliance versions versions
     * and also soft delete all the appliance version parameters.
     */
    public function testDeleteAppliance()
    {
        $appliance = $this->appliances[0];
        $applianceVersion = $appliance->getLatestVersion();
        $parameters = $applianceVersion->parameters;

        $this->assertNull($appliance->deleted_at);

        // get the parameters for the appliance version and check that they are marked as deleted
        foreach ($parameters as $parameter) {
            $this->assertNull($parameter->deleted_at);
        }

        $this->assertNull($applianceVersion->deleted_at);

        $this->json('DELETE', '/v1/appliances/' . $appliance->uuid, [], $this->validWriteHeaders);

        $this->assertResponseStatus(204);

        // Check things were marked as deleted
        $appliance->refresh();

        $this->assertNotNull($appliance->deleted_at);

        $applianceVersion->refresh();

        $this->assertNotNull($applianceVersion->deleted_at);

        foreach ($parameters as $parameter) {
            $parameter->refresh();
            $this->assertNotNull($parameter->deleted_at);
        }
    }

    public function testDeleteApplianceUnauthorised()
    {
        $appliance = $this->appliances[0];

        $this->json('DELETE', '/v1/appliances/' . $appliance->uuid, [], $this->validReadHeaders);

        $this->assertResponseStatus(401);
    }
}
