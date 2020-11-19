<?php

namespace Tests\unit\Support;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\FloatingIp;
use App\Models\V2\Instance;
use App\Models\V2\Nic;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use App\Support\Resource;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class ResourceTest extends TestCase
{
    use DatabaseMigrations;

    protected $region;
    protected $availability_zone;
    protected $vpc;
    protected $instance;
    protected $floating_ip;
    protected $nic;

    public function setUp(): void
    {
        parent::setUp();

        $this->region = factory(Region::class)->create();
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->id,
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->id,
        ]);
        $this->instance = factory(Instance::class)->create([
            'availability_zone_id' => $this->availability_zone->id,
            'vpc_id' => $this->vpc->id,
        ]);
        $this->floating_ip = factory(FloatingIp::class)->create();
        $this->nic = factory(Nic::class)->create();
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
