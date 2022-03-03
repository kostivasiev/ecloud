<?php

namespace Tests\V1\Appliances\ApplianceVersions;

use App\Models\V1\Appliance;
use App\Models\V1\AppliancePodAvailability;
use App\Models\V1\ApplianceVersion;
use Tests\V1\ApplianceTestCase;


class DeleteTest extends ApplianceTestCase
{
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

        $this->json('DELETE', '/v1/appliance-versions/' . $applianceVersion->uuid, [], $this->validWriteHeaders);

        $this->assertStatus(204);

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

        $this->json('DELETE', '/v1/appliance-versions/' . $applianceVersion->uuid, [], $this->validReadHeaders);

        $this->assertStatus(401);
    }

    /**
     * Test to make sure that we can't delete the last active version of an appliance if the appliance is
     * active in any Pods
     */
    public function testDeleteLastActiveApplianceVersion()
    {
        // Create an appliance with a single active version and add it to a Pod
        $appliance = factory(Appliance::class, 1)->create()->each(function ($appliance) {
            $appliance->save();
            $appliance->refresh();

            // Create a single appliance version
            $applianceFactoryConfig = [
                'appliance_version_appliance_id' => $appliance->id,
                'appliance_version_version' => 1,
            ];

            $applianceVersion = factory(ApplianceVersion::class)->make($applianceFactoryConfig);
            $applianceVersion->save();
            $applianceVersion->refresh();

            // Add an appliance to a pod
            $availability = new AppliancePodAvailability(); //
            $availability->appliance_id = $appliance->id;
            $availability->ucs_datacentre_id = 1;
            $availability->save();
        })->first();

        $versions = $appliance->versions->where('appliance_version_active', '=', 'Yes');
        $this->assertEquals(1, $versions->count());

        $res = $this->json(
            'DELETE',
            '/v1/appliance-versions/' . $versions->first()->appliance_version_uuid,
            [],
            $this->validWriteHeaders
        );

        $this->assertStatus(400);
    }
}
