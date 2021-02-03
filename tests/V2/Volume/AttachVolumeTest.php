<?php
namespace App\Tests\Volume;

use App\Models\V2\Instance;
use App\Models\V2\Volume;
use Illuminate\Support\Str;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class AttachVolumeTest extends TestCase
{
    use DatabaseMigrations;

    protected Instance $instance;
    protected Volume $volume;

    public function setUp(): void
    {
        parent::setUp();
        $this->instance = factory(Instance::class)->create([
            'id' => 'i-abc123xyz',
            'name' => 'i-abc123xyz',
            'vpc_id' => $this->vpc()->id,
            'appliance_version_id' => Str::uuid(),
            'vcpu_cores' => 1,
            'ram_capacity' => 8,
            'availability_zone_id' => $this->availabilityZone()->id,
            'locked' => false,
            'platform' => 'Linux',
            'backup_enabled' => false,
        ]);
        $this->volume = factory(Volume::class)->create([
            'id' => 'vol-abc123xyz',
            'name' => 'vol-abc123xyz',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'capacity' => 30,
            'vmware_uuid' => Str::uuid(),
        ]);
    }

    public function testAttachingVolume()
    {
        $this->post(
            '/v2/volumes/'.$this->volume->id.'/attach',
            [
                'instance_id' => $this->instance->id,
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )->assertResponseStatus(202);

        $attached = $this->volume->instances()->where('id', '=', $this->instance->id)->first();
        $this->assertEquals($this->instance->id, $attached->id);
        $this->assertEquals($this->instance->name, $attached->name);
        $this->assertEquals($this->instance->vpc_id, $attached->vpc_id);
        $this->assertEquals($this->instance->appliance_version_id, $attached->appliance_version_id);
    }

    public function testAttachingAlreadyAttachedVolume()
    {
        $this->volume->instances()->attach($this->instance);
        $this->post(
            '/v2/volumes/'.$this->volume->id.'/attach',
            [
                'instance_id' => $this->instance->id,
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The specified volume is already mounted on this instance',
            'status' => 422,
            'source' => 'instance_id',
        ])->assertResponseStatus(422);
    }

    public function testAttachVolumeToInstanceWithVolumes()
    {
        $volume = factory(Volume::class)->create([
            'id' => 'vol-vvv111vvv',
            'name' => 'vol-vvv111vvv',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'capacity' => 30,
            'vmware_uuid' => Str::uuid(),
        ]);
        $volume->instances()->attach($this->instance);
        $this->post(
            '/v2/volumes/'.$this->volume->id.'/attach',
            [
                'instance_id' => $this->instance->id,
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )->assertResponseStatus(202);

        // Verify the mounted volumes
        $this->assertEquals(2, $this->instance->volumes()->get()->count());
        $mountedVolumes = $this->instance->volumes()->get()->toArray();
        $this->assertEquals($volume->id, $mountedVolumes[0]['id']);
        $this->assertEquals($this->volume->id, $mountedVolumes[1]['id']);
    }
}