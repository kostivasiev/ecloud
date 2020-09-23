<?php

namespace Tests\V2\Instances;

use App\Models\V2\Appliance;
use App\Models\V2\ApplianceVersion;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Instance;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected $availability_zone;
    protected $vpc;
    protected $appliance;
    protected $appliance_version;
    protected $instance;
    protected $region;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->region = factory(Region::class)->create();
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        Vpc::flushEventListeners();
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->appliance = factory(Appliance::class)->create([
            'appliance_name' => 'Test Appliance',
        ]);
        $this->appliance_version = factory(ApplianceVersion::class)->create([
            'appliance_version_appliance_id' => $this->appliance->appliance_id,
        ])->refresh();
        $this->instance = factory(Instance::class)->create([
            'vpc_id' => $this->vpc->getKey(),
            'name' => 'DeleteTest Default',
            'appliance_version_id' => $this->appliance_version->appliance_version_uuid,
            'vcpu_cores' => 1,
            'ram_capacity' => 1024,
        ]);
    }

    public function testSuccessfulDelete()
    {
        $this->delete(
            '/v2/instances/' . $this->instance->getKey(),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
        $instance = Instance::withTrashed()->findOrFail($this->instance->getKey());
        $this->assertNotNull($instance->deleted_at);
    }

    public function testAdminInstanceLocking()
    {
        // Lock the instance
        $this->instance->locked = true;
        $this->instance->save();
        $this->delete(
            '/v2/instances/' . $this->instance->getKey(),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
        $instance = Instance::withTrashed()->findOrFail($this->instance->getKey());
        $this->assertNotNull($instance->deleted_at);
    }

    public function testNonAdminInstanceLocking()
    {
        // First lock the instance
        $this->instance->locked = true;
        $this->instance->save();
        $this->delete(
            '/v2/instances/' . $this->instance->getKey(),
            [],
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Forbidden',
                'detail' => 'The specified instance is locked',
                'status' => 403,
            ])
            ->assertResponseStatus(403);
        // Now unlock the instance
        $this->instance->locked = false;
        $this->instance->save();
        $this->delete(
            '/v2/instances/' . $this->instance->getKey(),
            [],
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
        $instance = Instance::withTrashed()->findOrFail($this->instance->getKey());
        $this->assertNotNull($instance->deleted_at);
    }
}
