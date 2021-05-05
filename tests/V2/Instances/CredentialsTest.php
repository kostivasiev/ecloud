<?php

namespace Tests\V2\Instances;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Credential;
use App\Models\V2\Instance;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CredentialsTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;

    protected $vpc;

    protected $instance;

    protected $credential;

    public function setUp(): void
    {
        parent::setUp();

        $region = factory(Region::class)->create();
        $availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $region->id,
        ]);
        $this->instance = Instance::withoutEvents(function () use ($availabilityZone) {
            return factory(Instance::class)->create([
                'id' => 'i-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $availabilityZone->id,
                'deploy_data' => [
                    'network_id' => $this->network()->id,
                    'volume_capacity' => 20,
                    'volume_iops' => 300,
                    'requires_floating_ip' => false,
                ],
                'deployed' => true,
            ]);
        });

        $this->credential = factory(Credential::class)->create([
            'resource_id' => $this->instance->id
        ]);
    }

    public function testGetCredentials()
    {
        $this->get(
            '/v2/instances/' . $this->instance->id . '/credentials',
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson(
                collect($this->credential)
                    ->except(['created_at', 'updated_at'])
                    ->toArray()
            )
            ->assertResponseStatus(200);
    }

    public function testGetCredentialsWhenNotDeployed()
    {
        $this->instance->deployed = false;
        $this->instance->saveQuietly();
        $this->get(
            '/v2/instances/' . $this->instance->id . '/credentials',
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->seeJson(
            [
                'title' => 'Not Found',
                'detail' => 'Credentials will be available when instance deployment is complete'
            ]
        )->assertResponseStatus(404);
    }

    public function testGetCredentialsWhenAdmin()
    {
        $this->instance->deployed = false;
        $this->instance->saveQuietly();
        $this->get(
            '/v2/instances/' . $this->instance->id . '/credentials',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson(
                collect($this->credential)
                    ->except(['created_at', 'updated_at'])
                    ->toArray()
            )
            ->assertResponseStatus(200);
    }
}
