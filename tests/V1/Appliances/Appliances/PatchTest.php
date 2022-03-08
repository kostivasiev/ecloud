<?php

namespace Tests\V1\Appliances\Appliances;

use App\Models\V1\Appliance;
use Tests\V1\ApplianceTestCase;
use UKFast\DB\Ditto\Factories\FilterFactory;

class PatchTest extends ApplianceTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test updating an Appliance
     * @return void
     */
    public function testUpdateAppliance()
    {
        $testString = 'phpUnit test string';

        $uuid = $this->appliances->first()->uuid;

        $this->assertDatabaseMissing(
            'appliance',
            [
                'appliance_uuid' => $uuid,
                'appliance_name' => $testString,
                'appliance_logo_uri' => $testString,
                'appliance_description' => $testString,
                'appliance_documentation_uri' => $testString,
                'appliance_publisher' => $testString,
                'appliance_active' => 'No'
            ],
            env('DB_ECLOUD_CONNECTION')
        );

        $this->json('PATCH', '/v1/appliances/' . $uuid, [
            'name' => $testString,
            'logo_uri' => $testString,
            'description' => $testString,
            'documentation_uri' => $testString,
            'publisher' => $testString,
            'active' => false
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertJsonFragment([
            'id' => $uuid
        ]);

        $this->assertDatabaseHas(
            'appliance',
            [
                'appliance_uuid' => $uuid,
                'appliance_name' => $testString,
                'appliance_logo_uri' => $testString,
                'appliance_description' => $testString,
                'appliance_documentation_uri' => $testString,
                'appliance_publisher' => $testString,
                'appliance_active' => 'No'
            ],
            env('DB_ECLOUD_CONNECTION')
        );
    }

    // test non admin
    public function testUpdateApplianceNotAdmin()
    {
        /**
         * Loop over each property and try and update it
         */
        $testString = 'phpUnit test string';

        $uuid = $this->appliances->first()->uuid;

        $this->json('PATCH', '/v1/appliances/' . $uuid, [
            'name' => $testString,
        ], [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(401);
    }
}
