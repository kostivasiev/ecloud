<?php
namespace Tests\V2\Volume;

use App\Models\V2\Appliance;
use App\Models\V2\ApplianceVersion;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Instance;
use App\Models\V2\Region;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class IopsModificationTest extends TestCase
{
    use DatabaseMigrations;

    protected Appliance $appliance;
    protected ApplianceVersion $appliance_version;
    protected AvailabilityZone $availabilityZone;
    protected Instance $instance;
    protected Region $region;
    protected Volume $volume;
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
        $this->volume = factory(Volume::class)->create([
            'vpc_id' => $this->vpc->getKey()
        ]);
        $this->instance = factory(Instance::class)->create([
            'vpc_id' => $this->vpc->getKey(),
            'name' => 'GetTest Default',
            'appliance_version_id' => $this->appliance_version->uuid,
            'vcpu_cores' => 1,
            'ram_capacity' => 1024,
            'platform' => 'Linux',
        ]);
        $this->instance->volumes()->save($this->volume);
    }

    public function testSetValidIopsValue()
    {
        $data = [
            'iops' => 300,
        ];
        $this->patch(
            '/v2/volumes/'.$this->volume->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeInDatabase(
            'volumes',
            [
                'id' => $this->volume->getKey(),
                'iops' => $data['iops'],
            ],
            'ecloud'
        )->assertResponseStatus(200);
    }

    public function testSetInvalidIopsValue()
    {
        $data = [
            'iops' => 200,
        ];
        $this->patch(
            '/v2/volumes/'.$this->volume->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The iops does not contain a valid iops value',
            'source' => 'iops',
        ])->assertResponseStatus(422);
    }

    public function testSetIopsOnUnmountedVolume()
    {
        $this->instance->volumes()->detach($this->volume);
        $data = [
            'iops' => 200,
        ];
        $this->patch(
            '/v2/volumes/'.$this->volume->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The Iops value can only be set on mounted volumes',
            'source' => 'iops',
        ])->assertResponseStatus(422);
    }

}
