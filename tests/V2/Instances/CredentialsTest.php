<?php

namespace Tests\V2\Instances;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Credential;
use App\Models\V2\Instance;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class CredentialsTest extends TestCase
{
    protected $faker;

    protected $vpc;

    protected $instance;

    protected $credential;

    public function setUp(): void
    {
        parent::setUp();

        $region = Region::factory()->create();
        $availabilityZone = AvailabilityZone::factory()->create([
            'region_id' => $region->id,
        ]);
        $this->instance = Instance::withoutEvents(function () use ($availabilityZone) {
            return Instance::factory()->create([
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

        $this->credential = Credential::factory()->create([
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
            ->assertJsonFragment(
                collect($this->credential)
                    ->except(['created_at', 'updated_at'])
                    ->toArray()
            )
            ->assertStatus(200);
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
        )->assertJsonFragment(
            [
                'title' => 'Not Found',
                'detail' => 'Credentials will be available when instance deployment is complete'
            ]
        )->assertStatus(404);
    }

    public function testGetCredentialsWhenNotDeployedAsAdmin()
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
            ->assertJsonFragment(
                collect($this->credential)
                    ->except(['created_at', 'updated_at'])
                    ->toArray()
            )
            ->assertStatus(200);
    }
}
