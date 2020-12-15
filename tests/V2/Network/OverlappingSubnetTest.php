<?php

namespace Tests\V2\Network;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Network;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use App\Rules\V2\IsNotOverlappingSubnet;
use Illuminate\Http\Request;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class OverlappingSubnetTest extends TestCase
{
    use DatabaseMigrations;

    protected AvailabilityZone $availabilityZone;
    protected Network $network;
    protected Region $region;
    protected Router $router;
    protected IsNotOverlappingSubnet $validator;
    protected Vpc $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->validator = new IsNotOverlappingSubnet();
        $this->region = factory(Region::class)->create([
            'name' => 'testregion',
        ]);
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc->getKey(),
            'availability_zone_id' => $this->availabilityZone->getKey(),
        ]);
        $this->network = factory(Network::class)->create([
            'router_id' => $this->router->getKey(),
            'subnet' => '10.0.0.1/30',
        ]);
        app()->bind('request', function () {
            $request = new Request();
            $request->merge([
                'router_id' => $this->router->getKey(),
            ]);
            return $request;
        });
    }

    public function testNoOverlap()
    {
        $this->assertTrue($this->validator->passes('', '10.0.1.1/30'));
    }

    public function testOverlap()
    {
        $this->assertFalse($this->validator->passes('', '10.0.0.1/24'));
    }
}
