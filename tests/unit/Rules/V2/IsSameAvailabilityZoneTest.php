<?php

namespace Tests\unit\Rules\V2;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\AvailabilityZoneable;
use App\Models\V2\FloatingIp;
use App\Models\V2\Network;
use App\Models\V2\Nic;
use App\Models\V2\Router;
use App\Models\V2\Volume;
use App\Rules\V2\IsSameAvailabilityZone;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class IsSameAvailabilityZoneTest extends TestCase
{
    public AvailabilityZone $availabilityZone2;

    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->availabilityZone2 = factory(AvailabilityZone::class)->create([
            'id' => 'az-test-2',
            'region_id' => $this->region()->id,
        ]);
    }

    // Instance deploy
    public function testAssigningFloatingIpToInstanceInSameAvailabilityZoneSucceeds()
    {
        $this->assertTrue($this->floatingIp() instanceof AvailabilityZoneable);
        $this->assertTrue($this->network() instanceof AvailabilityZoneable);

        $rule = new IsSameAvailabilityZone($this->network()->id);

        $result = $rule->passes('floating_ip_id', $this->floatingIp()->id);

        $this->assertTrue($result);
    }

    // Instance deploy
    public function testAssigningFloatingIpToInstanceInDifferentAvailabilityZoneFails()
    {
        $rule = new IsSameAvailabilityZone($this->network()->id);

        $floatingIp = factory(floatingip::class)->create([
            'id' => 'fip-test',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone2->id,
        ]);

        $result = $rule->passes('floating_ip_id', $floatingIp->id);

        $this->assertFalse($result);
    }

    // Assigning existing floating IP to an instance (via assigning to the NIC attached to the instance)
    public function testAssigningFloatingIpToNicSameAvailabilityZoneSucceeds()
    {
        $this->assertTrue($this->floatingIp() instanceof AvailabilityZoneable);
        $this->assertTrue($this->nic() instanceof AvailabilityZoneable);

        $rule = new IsSameAvailabilityZone($this->floatingIp()->id);

        $result = $rule->passes('resource_id', $this->nic()->id);

        $this->assertTrue($result);
    }

    // Assigning existing floating IP to an instance (via assigning to the NIC attached to the instance)
    public function testAssigningFloatingIpToNicDifferentAvailabilityZoneFails()
    {
        $rule = new IsSameAvailabilityZone($this->floatingIp()->id);

        $nic = Nic::withoutEvents(function() {
            $router = factory(Router::class)->create([
                'id' => 'rtr-test-2',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone2->id,
                'router_throughput_id' => $this->routerThroughput()->id,
            ]);

            $network = factory(Network::class)->create([
                'id' => 'net-test-2',
                'name' => 'Manchester Network',
                'subnet' => '10.0.0.0/24',
                'router_id' => $router->id
            ]);

            return factory(Nic::class)->create([
                'id' => 'nic-test',
                'mac_address' => 'AA:BB:CC:DD:EE:FF',
                'network_id' => $network->id,
            ]);
        });

        $result = $rule->passes('resource_id', $nic->id);

        $this->assertFalse($result);
    }

    /**
     * Volumes
     */

    // Instance attach volume endpoint
    public function testInstanceAttachVolumeSameAvailabilityZoneSucceeds()
    {
        $this->assertTrue($this->instanceModel() instanceof AvailabilityZoneable);

        $rule = new IsSameAvailabilityZone($this->instanceModel()->id);

        $volume = Volume::factory()->create([
            'id' => 'vol-test',
            'availability_zone_id' => $this->availabilityZone()->id,
            'vpc_id' => $this->vpc()->id
        ]);

        $this->assertTrue($volume instanceof AvailabilityZoneable);

        $result = $rule->passes('volume_id', $volume->id);

        $this->assertTrue($result);
    }

    // Instance attach volume endpoint
    public function testInstanceAttachVolumeDifferentAvailabilityZoneFails()
    {
        $rule = new IsSameAvailabilityZone($this->instanceModel()->id);

        $volume = Volume::factory()->create([
            'id' => 'vol-test',
            'availability_zone_id' => $this->availabilityZone2->id,
            'vpc_id' => $this->vpc()->id
        ]);

        $result = $rule->passes('volume_id', $volume->id);

        $this->assertFalse($result);
    }

    // Volume attach instance endpoint
    public function testVolumeAttachInstanceSameAvailabilityZoneSucceeds()
    {
        $volume = Volume::factory()->create([
            'id' => 'vol-test',
            'availability_zone_id' => $this->availabilityZone()->id,
            'vpc_id' => $this->vpc()->id
        ]);

        $this->assertTrue($volume instanceof AvailabilityZoneable);

        $rule = new IsSameAvailabilityZone($volume->id);

        $this->assertTrue($this->instanceModel() instanceof AvailabilityZoneable);

        $result = $rule->passes('instance_id', $this->instanceModel()->id);

        $this->assertTrue($result);
    }

    // Volume attach instance endpoint
    public function testVolumeAttachInstanceDifferentAvailabilityZoneFails()
    {
        $volume = Volume::factory()->create([
            'id' => 'vol-test',
            'availability_zone_id' => $this->availabilityZone2->id,
            'vpc_id' => $this->vpc()->id
        ]);

        $rule = new IsSameAvailabilityZone($volume->id);

        $result = $rule->passes('instance_id', $this->instanceModel()->id);

        $this->assertFalse($result);
    }

    public function testUnknownResourceClassFromIdFails()
    {
        $volume = Volume::factory()->create([
            'id' => 'vol-test',
            'availability_zone_id' => $this->availabilityZone2->id,
            'vpc_id' => $this->vpc()->id
        ]);

        $rule = new IsSameAvailabilityZone('invalid-test');

        $result = $rule->passes('instance_id', $this->instanceModel()->id);

        $this->assertFalse($result);
    }

    public function testUnknownValueClassFromIdFails()
    {
        $volume = Volume::factory()->create([
            'id' => 'vol-test',
            'availability_zone_id' => $this->availabilityZone2->id,
            'vpc_id' => $this->vpc()->id
        ]);

        $rule = new IsSameAvailabilityZone($this->instanceModel()->id);

        $result = $rule->passes('invalid', 'invalid-test');

        $this->assertFalse($result);
    }
}