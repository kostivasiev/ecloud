<?php

namespace Tests\unit\Rules\V2\Volume;

use App\Models\V2\Instance;
use App\Rules\V2\Volume\HasAvailableInstances;
use Illuminate\Support\Facades\Config;
use Tests\Mocks\Resources\VolumeGroupMock;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class HasAvailableInstancesTest extends TestCase
{
    use VolumeGroupMock;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testNoAvailableInstancesFails()
    {
        Config::set('volume-group.max_instances', 1);
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        Instance::withoutEvents(function () {
            Instance::factory()->create([
                'id' => 'i-test',
                'vpc_id' => $this->vpc()->id,
                'name' => 'Test Instance ' . uniqid(),
                'image_id' => $this->image()->id,
                'vcpu_cores' => 1,
                'ram_capacity' => 1024,
                'availability_zone_id' => $this->availabilityZone()->id,
                'volume_group_id' => $this->volumeGroup()->id,
            ]);
        });

        $rule = new HasAvailableInstances();

        $result = $rule->passes('volume_group_id', $this->volumeGroup()->id);

        $this->assertFalse($result);
    }

    public function testAvailableInstancesPasses()
    {
        Config::set('volume-group.max_instances', 2);
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        $rule = new HasAvailableInstances();

        $result = $rule->passes('volume_group_id', $this->volumeGroup()->id);

        $this->assertTrue($result);
    }
}