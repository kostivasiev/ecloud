<?php

namespace Tests\unit\Rules\V2\Volume;

use App\Models\V2\Volume;
use App\Rules\V2\Volume\HasAvailablePorts;
use Illuminate\Support\Facades\Config;
use Tests\Mocks\Resources\VolumeGroupMock;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class HasAvailablePortsTest extends TestCase
{
    use VolumeGroupMock;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testNoAvailablePortsFails()
    {
        Config::set('volume-group.max_ports', 1);
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        Volume::factory()->create([
            'id' => 'vol-abc123xyz',
            'vpc_id' => $this->vpc()->id,
            'volume_group_id' => $this->volumeGroup()->id
        ]);

        $rule = new HasAvailablePorts();

        $result = $rule->passes('volume_group_id', $this->volumeGroup()->id);

        $this->assertFalse($result);
    }

    public function testAvailablePortsPasses()
    {
        Config::set('volume-group.max_ports', 10);
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        $rule = new HasAvailablePorts();

        $result = $rule->passes('volume_group_id', $this->volumeGroup()->id);

        $this->assertTrue($result);
    }
}