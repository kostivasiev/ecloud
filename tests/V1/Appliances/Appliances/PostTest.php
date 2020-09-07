<?php

namespace Tests\V1\Appliances\Appliances;

use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

use App\Models\V1\Appliance;

use Tests\ApplianceTestCase;

class PostTest extends ApplianceTestCase
{
    use DatabaseMigrations;

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
        $this->assertResponseStatus(201) && $this->seeInDatabase('appliance', [
            'appliance_uuid' => $uuid,
            'name' => $appliance->name,
            'logo_uri' => $appliance->logo_uri,
            'description' => $appliance->description,
            'documentation_uri' => $appliance->documentation_uri,
            'publisher' => $appliance->publisher,
            'active' => ($appliance->active == 'Yes'),
        ]);
    }
}
