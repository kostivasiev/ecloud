<?php

namespace Tests\V1\Appliances\Appliances;

use App\Models\V1\Appliance;
use App\Models\V1\AppliancePodAvailability;
use App\Models\V1\ApplianceVersion;
use App\Models\V1\Pod;
use Illuminate\Http\Response;
use Ramsey\Uuid\Uuid;
use Tests\V1\ApplianceTestCase;
use Tests\V1\Appliance\Version\DataTest;

class GetTest extends ApplianceTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test GET Appliance collection
     */
    public function testValidCollection()
    {
        $this->get('/v1/appliances', $this->validReadHeaders)
            ->assertJsonFragment([
            'total' => count($this->appliances),
        ])->assertStatus(200);
    }

    /**
     * Test GET Appliance Item
     */
    public function testValidItem()
    {
        $uuid = $this->appliances->first()->uuid;

        $this->get('/v1/appliances/' . $uuid, $this->validReadHeaders)->assertStatus(200);
    }

    /**
     * Test GET Appliance - Invalid item
     * @throws \Exception
     */
    public function testInvalidItem()
    {
        $this->get('/v1/appliances/' . Uuid::uuid4()->toString(), $this->validReadHeaders)->assertStatus(404);
    }

    /**
     * Test GET Appliance
     */
    public function testInvalidUuid()
    {
        $this->get('/v1/appliances/abc', $this->validReadHeaders)->assertStatus(422);
    }

    /**
     * Test GET Appliance parameters
     */
    public function testApplianceParameters()
    {
        $appliance = $this->appliances[0];
        $parameters = $appliance->getLatestVersion()->parameters;

        $this->json('GET', '/v1/appliances/' . $appliance->uuid . '/parameters', [], $this->validWriteHeaders)
            ->assertStatus(200)
            ->assertJsonFragment([
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
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $version->uuid,
                'appliance_id' => $version->appliance_uuid,
                'version' => (int)$version->version,
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
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $version->uuid,
                'appliance_id' => $version->appliance_uuid,
                'version' => (int)$version->version,
                'script_template' => $version->script_template,
                'vm_template' => $version->vm_template,
                'active' => ($version->active == 'Yes')
            ]);
    }

    /**
     * Test listing pods that an appliance is on
     */
    public function testPodsApplianceIsOn()
    {
        $appliance = $this->appliances[0];

        $pod = factory(Pod::class, 1)->create();

        $appliancePodAvailability = new AppliancePodAvailability();
        $appliancePodAvailability->appliance_id = $appliance->appliance_id;
        $appliancePodAvailability->ucs_datacentre_id = 1;
        $appliancePodAvailability->save();

        $this->json('GET', '/v1/appliances/' . $appliance->uuid . '/pods', [], $this->validWriteHeaders)
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $pod[0]->getKey()
            ]);
    }

    public function testApplianceData()
    {
        $appliance = factory(Appliance::class)->create();
        $applianceVersion = ApplianceVersion::factory()->create([
            'appliance_uuid' => $appliance->appliance_uuid,
            'appliance_version_version' => 1,
        ]);
        $applianceVersionData = factory(Appliance\Version\Data::class)->create([
            'appliance_version_uuid' => $applianceVersion->appliance_version_uuid,
            'key' => 'key_value',
            'value' => 'value_value',
        ]);

        $this->json(
            'GET',
            '/v1/appliances/' . $appliance->uuid . '/data',
            [],
            DataTest::HEADERS_ADMIN
        )->assertStatus(Response::HTTP_OK)->assertJsonFragment([
            'data' => [
                [
                    'key' => $applianceVersionData->key,
                    'value' => $applianceVersionData->value,
                ]
            ]
        ]);
    }
}
