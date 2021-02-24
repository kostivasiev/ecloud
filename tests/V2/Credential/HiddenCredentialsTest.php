<?php

namespace Tests\V2\Credential;

use App\Models\V2\Appliance;
use App\Models\V2\ApplianceVersion;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Credential;
use App\Models\V2\Instance;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class HiddenCredentialsTest extends TestCase
{
    use DatabaseMigrations;

    protected Appliance $appliance;
    protected ApplianceVersion $appliance_version;
    protected AvailabilityZone $availabilityZone;
    protected Credential $credentials;
    protected Instance $instance;
    protected Region $region;
    protected Vpc $vpc;


    public function setUp(): void
    {
        parent::setUp();

        $this->region = factory(Region::class)->create();
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->appliance = factory(Appliance::class)->create([
            'appliance_name' => 'Test Appliance',
        ])->refresh();
        $this->appliance_version = factory(ApplianceVersion::class)->create([
            'appliance_version_appliance_id' => $this->appliance->id,
        ])->refresh();
        $this->instance = factory(Instance::class)->create([
            'vpc_id' => $this->vpc->getKey(),
            'name' => 'GetTest Default',
            'appliance_version_id' => $this->appliance_version->uuid,
            'vcpu_cores' => 1,
            'ram_capacity' => 1024,
            'platform' => 'Linux',
            'availability_zone_id' => $this->availabilityZone->id
        ]);

        $this->credentials = factory(Credential::class)->create([
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
                'resource_id' => $this->instance->getKey(),
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
        )->seeInDatabase(
            'credentials',
            [
                'resource_id' => 'abc-abc132',
                'host' => 'https://127.0.0.1',
                'username' => 'someuser',
                'port' => 8080,
                'is_hidden' => 1,
            ],
            'ecloud'
        )->assertResponseStatus(201);
    }

    public function testAdminCanSeeHiddenCredentials()
    {
        $this->get(
            '/v2/credentials/' . $this->credentials->getKey(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->seeJson([
            'is_hidden' => true,
        ])->assertResponseStatus(200);
    }

    public function testUserCannotSeeHiddenFlag()
    {
        $this->get(
            '/v2/instances/' . $this->instance->getKey() . '/credentials',
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->dontSeeJson([
            'is_hidden' => true,
        ])->assertResponseStatus(200);
    }
}
