<?php

namespace Tests\V1\Appliances\Appliances;

use Tests\V1\ApplianceTestCase;

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
        /**
         * Loop over each property and try and update it
         */
        $testString = 'phpUnit test string';

        $stringProperty = [
            'name' => $testString,
            'logo_uri' => $testString,
            'description' => $testString,
            'documentation_uri' => $testString,
            'publisher' => $testString,
            'active' => false
        ];

        $uuid = $this->appliances->first()->uuid;

        foreach ($stringProperty as $property => $newValue) {
            $this->missingFromDatabase(
                'appliance',
                [
                    'appliance_uuid' => $uuid,
                    $property => $newValue
                ],
                env('DB_ECLOUD_CONNECTION')
            );

            $this->json('PATCH', '/v1/appliances/' . $uuid, [
                $property => $newValue,
            ], [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]);

            $this->seeJson([
                'id' => $uuid
            ]);

            $this->assertResponseStatus(200) && $this->seeInDatabase('appliance', [
                'appliance_uuid' => $uuid,
                $property => $newValue,
            ]);
        }
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
        ]);

        $this->assertResponseStatus(401);
    }
}
