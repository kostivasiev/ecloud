<?php

namespace Tests\V1\Appliances\Pods;

use Tests\V1\ApplianceTestCase;

class GetTest extends ApplianceTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        parent::setUpAppliancePodTestData();
    }

    /**
     * Test GET Appliance on a Pod
     */
    public function testPodAppliances()
    {
        $this->json('GET', '/v1/pods/1/appliances', [], $this->validWriteHeaders)
            ->seeStatusCode(200)
            ->seeJson(
                [
                    'id' => $this->appliances[0]->uuid,
                    'name' => $this->appliances[0]->name,
                    'logo_uri' => $this->appliances[0]->logo_uri,
                    'description' => $this->appliances[0]->description,
                    'documentation_uri' => $this->appliances[0]->documentation_uri,
                    'publisher' => $this->appliances[0]->publisher,
                ]);
    }

    public function testPodAppliancesPodDisabled()
    {
        $this->json('GET', '/v1/pods/2/appliances', [], $this->validReadHeaders) //$this->validWriteHeaders
        ->seeStatusCode(200)
            ->seeJson(
                [
                    'total' => 0,
                ]);
    }


}
