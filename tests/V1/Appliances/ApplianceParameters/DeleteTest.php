<?php

namespace Tests\V1\Appliances\ApplianceParameters;

use Tests\V1\ApplianceTestCase;

class DeleteTest extends ApplianceTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testDeleteApplianceParameter()
    {
        $parameter = $this->appliances[0]->getLatestVersion()->parameters[0];

        $this->assertNull($parameter->deleted_at);

        $this->json('DELETE', '/v1/appliance-parameters/' . $parameter->uuid, [], $this->validWriteHeaders);

        $this->assertResponseStatus(204);

        $parameter->refresh();

        $this->assertNotNull($parameter->deleted_at);
    }


    public function testDeleteApplianceParameterUnauthorised()
    {
        $parameter = $this->appliances[0]->getLatestVersion()->parameters[0];

        $this->json('DELETE', '/v1/appliance-parameters/' . $parameter->uuid, [], $this->validReadHeaders);

        $this->assertResponseStatus(401);
    }
}
