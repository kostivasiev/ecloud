<?php

namespace Tests\Appliances\Pods;

use Laravel\Lumen\Testing\DatabaseMigrations;

use Ramsey\Uuid\Uuid;

use Tests\ApplianceTestCase;

class GetTest extends ApplianceTestCase
{
    use DatabaseMigrations;

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
