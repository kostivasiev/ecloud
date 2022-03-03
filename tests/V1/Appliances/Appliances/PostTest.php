<?php

namespace Tests\V1\Appliances\Appliances;

use App\Models\V1\Appliance;
use DB;
use Tests\V1\ApplianceTestCase;

class PostTest extends ApplianceTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testCreateAppliance()
    {
        // Generate test appliance record
        $appliance = factory(Appliance::class, 1)->make()->first();

        // Assert record does not exist
        $this->missingFromDatabase(
            'appliance',
            [
                'appliance_uuid' => $appliance->appliance_uuid
            ],
            env('DB_ECLOUD_CONNECTION')
        );

        // Create the appliance record
        $res = $this->json('POST', '/v1/appliances', [
            'name' => $appliance->name,
            'logo_uri' => $appliance->logo_uri,
            'description' => $appliance->description,
            'documentation_uri' => $appliance->documentation_uri,
            'publisher' => $appliance->publisher,
            'active' => ($appliance->active == 'Yes'),
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ]);

        // Get the ID of the created record
        $data = json_decode($res->response->getContent());

        $uuid = $data->data->id;

        // Check that the appliance was created
        $this->assertDatabaseHas(
            'appliance',
            [
                'appliance_uuid' => $uuid,
                'appliance_name' => $appliance->name,
                'appliance_logo_uri' => $appliance->logo_uri,
                'appliance_description' => $appliance->description,
                'appliance_documentation_uri' => $appliance->documentation_uri,
                'appliance_publisher' => $appliance->publisher,
                'appliance_active' => ($appliance->active == 'Yes'),
            ],
            env('DB_ECLOUD_CONNECTION')
        );
    }
}
