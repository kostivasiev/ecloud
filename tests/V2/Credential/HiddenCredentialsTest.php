<?php

namespace Tests\V2\Credential;

use App\Models\V2\Appliance;
use App\Models\V2\ApplianceVersion;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Credential;
use App\Models\V2\Image;
use App\Models\V2\Instance;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Tests\TestCase;

class HiddenCredentialsTest extends TestCase
{
    protected Appliance $appliance;
    protected ApplianceVersion $appliance_version;
    protected AvailabilityZone $availabilityZone;
    protected Credential $credentials;
    protected Instance $instance;
    protected Region $region;
    protected Vpc $vpc;
    protected Image $image;

    public function setUp(): void
    {
        parent::setUp();

        Instance::withoutEvents(function () {
            $this->instance = new Instance(['id' => 'abc-abc132']);
        });

        $this->credentials = Credential::factory()->create([
            'resource_id' => 'abc-abc132',
            'host' => 'https://127.0.0.1',
            'username' => 'someuser',
            'password' => 'somepassword',
            'port' => 8080,
            'is_hidden' => true,
        ]);
    }

    public function testAdminCanSetHiddenFlag()
    {
        $this->post(
            '/v2/credentials',
            [
                'resource_id' => $this->instanceModel()->id,
                'host' => 'https://127.0.0.1',
                'username' => 'someuser',
                'password' => 'somepassword',
                'port' => 8080,
                'is_hidden' => true,
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertStatus(201);

        $this->assertDatabaseHas(
            'credentials',
            [
                'resource_id' => $this->instanceModel()->id,
                'host' => 'https://127.0.0.1',
                'username' => 'someuser',
                'port' => 8080,
                'is_hidden' => 1,
            ],
            'ecloud'
        );
    }

    public function testAdminCanSeeHiddenCredentials()
    {
        $this->get(
            '/v2/credentials/' . $this->credentials->id,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->assertJsonFragment([
            'is_hidden' => true,
        ])->assertStatus(200);
    }

    public function testUserCannotSeeHiddenFlag()
    {
        $this->instanceModel()->deployed = true;
        $this->instanceModel()->saveQuietly();
        $this->get(
            '/v2/instances/' . $this->instanceModel()->id . '/credentials',
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->assertJsonMissing([
            'is_hidden' => true,
        ])->assertStatus(200);
    }
}
