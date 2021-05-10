<?php

namespace Tests\V1\Appliances\Pods;

use App\Models\V1\AppliancePodAvailability;
use App\Models\V1\Pod;
use Tests\V1\ApplianceTestCase;

class DeleteTest extends ApplianceTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Add the appliance to the pod
        $appliance = $this->appliances[0];
        $appliancePodAvailability = new AppliancePodAvailability();
        $appliancePodAvailability->appliance_id = $appliance->id;
        $appliancePodAvailability->ucs_datacentre_id = 123;
        $appliancePodAvailability->save();
    }

    /**
     * Test remove an appliance from a pod
     */
    public function testDeleteApplianceFromPod()
    {
        factory(Pod::class, 1)->create([
            'ucs_datacentre_id' => 123,
        ]);
        $appliance = $this->appliances[0];

        $podQry = AppliancePodAvailability::query();
        $podQry->where('appliance_pod_availability_appliance_id', '=', $appliance->id);
        $podQry->where('appliance_pod_availability_ucs_datacentre_id', '=', 123);

        $this->assertEquals($podQry->count(), 1);

        $this->json('DELETE', '/v1/pods/123/appliances/' . $appliance->uuid, [], $this->validWriteHeaders);

        $this->assertResponseStatus(204);

        $podQry = AppliancePodAvailability::query();
        $podQry->where('appliance_pod_availability_appliance_id', '=', $appliance->id);
        $podQry->where('appliance_pod_availability_ucs_datacentre_id', '=', 123);

        $this->assertEquals($podQry->count(), 0);
    }

    public function testDeleteApplianceFromPodUnauthorised()
    {
        $appliance = $this->appliances[0];
        factory(Pod::class, 1)->create([
            'ucs_datacentre_id' => 123,
        ]);

        $this->json('DELETE', '/v1/pods/123/appliances/' . $appliance->uuid, [], $this->validReadHeaders);

        $this->assertResponseStatus(401);
    }
}
