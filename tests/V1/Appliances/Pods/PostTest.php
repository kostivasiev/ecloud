<?php

namespace Tests\Appliances\Pods;

use App\Models\V1\AppliancePodAvailability;
use Laravel\Lumen\Testing\DatabaseMigrations;

use Tests\ApplianceTestCase;

class PostTest extends ApplianceTestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
        parent::setUpAppliancePodTestData();
    }

    public function testAddApplianceToPod()
    {
        // Assert record does not exist
        $this->missingFromDatabase(
            'appliance_pod_availability',
            [
                'appliance_pod_availability_appliance_id' => $this->appliances[1]->id
            ],
            env('DB_ECLOUD_CONNECTION')
        );

        $this->assertTrue($this->addToPodMock(2));

        $this->seeInDatabase('appliance_pod_availability',
            [
                'appliance_pod_availability_appliance_id' => (int) $this->appliances[1]->id
            ],
            env('DB_ECLOUD_CONNECTION')
        );
    }


    /**
     * Try to mock the add appliance to pod without the template check.
     * @param $podId
     * @return bool
     * @throws \Exception
     */
    public function addToPodMock($podId)
    {
        $appliance = $this->appliances[1];

        $row = new AppliancePodAvailability();
        $row->appliance_id = $appliance->id;
        $row->ucs_datacentre_id = $podId;
        try {
            $row->save();
        } catch (\Exception $exception) {
            $message = 'Unable to add Appliance to pod';
            if ($exception->getCode() == 23000) {
                $message .= ': The Appliance is already in this Pod.';
            }
            throw new \Exception($message);
        }

        return true;
    }

}
