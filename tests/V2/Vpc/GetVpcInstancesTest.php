<?php

namespace Tests\V2\Vpc;

use App\Models\V2\Appliance;
use App\Models\V2\ApplianceVersion;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Instance;
use App\Models\V2\Region;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetVpcInstancesTest extends TestCase
{
    use DatabaseMigrations;

    public Appliance $appliance;
    public ApplianceVersion $appliance_version;
    public AvailabilityZone $availabilityZone;
    public Region $region;
    public $instances;
    public Vpc $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->region = factory(Region::class)->create();

        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey()
        ]);

        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        $this->appliance = factory(Appliance::class)->create([
            'appliance_name' => 'Test Appliance',
        ])->refresh();
        $this->appliance_version = factory(ApplianceVersion::class)->create([
            'appliance_version_appliance_id' => $this->appliance->id,
        ])->refresh();
        $this->instances = factory(Instance::class, 4)->create([
            'vpc_id' => $this->vpc->getKey(),
            'name' => 'Test Instance ' . uniqid(),
            'appliance_version_id' => $this->appliance_version->uuid,
            'vcpu_cores' => 1,
            'ram_capacity' => 1024,
            'platform' => 'Linux',
        ]);
    }

    public function testInstancesCollection()
    {
        $this->get(
            '/v2/vpcs/'.$this->vpc->getKey().'/instances',
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups'    => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'       => $this->instances[0]->id,
                'name'     => $this->instances[0]->name,
                'vpc_id'   => $this->instances[0]->vpc_id,
                'platform' => $this->instances[0]->platform,
            ])
            ->assertResponseStatus(200);
    }
}
