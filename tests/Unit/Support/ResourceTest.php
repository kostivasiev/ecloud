<?php

namespace Tests\Unit\Support;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\FloatingIp;
use App\Models\V2\Instance;
use App\Models\V2\Nic;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use App\Support\Resource;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class ResourceTest extends TestCase
{
    protected $region;
    protected $availability_zone;
    protected $vpc;
    protected $instance;
    protected $floating_ip;
    protected $nic;

    public function setUp(): void
    {
        parent::setUp();

        Model::withoutEvents(function() {
            $this->region = Region::factory()->create([
                'id' => 'reg-test1',
            ]);
            $this->availability_zone = AvailabilityZone::factory()->create([
                'id' => 'az-test1',
                'region_id' => $this->region->id,
            ]);
            $this->vpc = Vpc::factory()->create([
                'id' => 'vpc-test1',
                'region_id' => $this->region->id,
            ]);
            $this->instance = Instance::factory()->create([
                'id' => 'i-test1',
                'availability_zone_id' => $this->availability_zone->id,
                'vpc_id' => $this->vpc->id,
            ]);
            $this->floating_ip = FloatingIp::factory()->create([
                'id' => 'fip-test1',
            ]);
            $this->nic = Nic::factory()->create([
                'id' => 'nic-test1',
            ]);
        });
    }

    public function loadFromIdReturnsCorrectResourcesProvider()
    {
        return [
            'region' => ['region', Region::class],
            'availability_zone' => ['availability_zone', AvailabilityZone::class],
            'vpc' => ['vpc', Vpc::class],
            'instance' => ['instance', Instance::class],
            'floating_ip' => ['floating_ip', FloatingIp::class],
            'nic' => ['nic', Nic::class],
        ];
    }

    /**
     * @param $classname
     * @param $class
     * @dataProvider loadFromIdReturnsCorrectResourcesProvider
     */
    public function testLoadFromIdReturnsCorrectResources($classname, $class)
    {
        $resource = Resource::classFromId($this->$classname->id);
        $this->assertTrue($resource == $class);
        $this->assertTrue($resource::find($this->$classname->id)->id == $this->$classname->id);
    }
}
