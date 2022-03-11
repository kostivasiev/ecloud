<?php

namespace Tests\V1\Appliances\ApplianceVersions;

use Illuminate\Foundation\Testing\DatabaseMigrations;;
use Tests\V1\ApplianceTestCase;

class PatchTest extends ApplianceTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test updating an Appliance version
     * @return void
     */
    public function testUpdateApplianceVersion()
    {
        /**
         * Loop over each property and try and update it
         */
        $scriptTemplate = 'phpUnit test string';

        $applianceVersion = $this->appliances[0]->getLatestVersion();

        // Add teh required params to the script template
        foreach ($applianceVersion->getParameterList(true) as $requiredParam) {
            $scriptTemplate .= "{{{ $requiredParam }}} ";
        }

        $stringProperty = [
            'version' => 9,
            'script_template' => $scriptTemplate . ' ',
            'vm_template' => 'sometemplate'
        ];

        foreach ($stringProperty as $property => $newValue) {
            $this->assertDatabaseMissing(
                'appliance_version',
                [
                    'appliance_version_uuid' => $applianceVersion->uuid,
                    $property => $newValue
                ],
                env('DB_ECLOUD_CONNECTION')
            );

            $this->json('PATCH', '/v1/appliance-versions/' . $applianceVersion->uuid, [
                $property => $newValue,
            ], $this->validWriteHeaders)
                ->assertStatus(200)
                ->assertJsonFragment([
                    'id' => $applianceVersion->uuid
                ]);

            $this->assertDatabaseHas(
                'appliance_version',
                [
                    'appliance_version_uuid' => $applianceVersion->uuid,
                    'appliance_version_' . $property => rtrim($newValue),
                ],
                env('DB_ECLOUD_CONNECTION')
            );
        }
    }

    /**
     * Test update an appliance version (non-admin)
     */
    public function testUpdateApplianceVersionNotAdmin()
    {
        /**
         * Loop over each property and try and update it
         */
        $testString = 'phpUnit test string';

        $applianceVersion = $this->appliances[0]->getLatestVersion();

        $this->json('PATCH', '/v1/appliance-versions/' . $applianceVersion->uuid, [
            'name' => $testString,
        ], $this->validReadHeaders)
            ->assertStatus(401);
    }
}
