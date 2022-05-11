<?php

namespace Tests\V2\Network;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Network;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use App\Rules\V2\IsNotOverlappingSubnet;
use Illuminate\Http\Request;
use Tests\TestCase;

class OverlappingSubnetTest extends TestCase
{
    protected AvailabilityZone $availabilityZone;
    protected Network $network;
    protected Region $region;
    protected Router $router;
    protected IsNotOverlappingSubnet $validator;
    protected Vpc $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->router = Router::factory()->create([
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
        ]);
        $this->network = Network::factory()->create([
            'router_id' => $this->router->id,
            'subnet' => '10.0.0.1/30',
        ]);
        app()->bind('request', function () {
            $request = new Request();
            $request->merge([
                'router_id' => $this->router->id,
            ]);
            return $request;
        });
    }

    public function testNoOverlap()
    {
        $this->validator = new IsNotOverlappingSubnet();
        $this->assertTrue($this->validator->passes('', '192.168.10.1/16'));
    }

    public function testOverlap()
    {
        $this->validator = new IsNotOverlappingSubnet();
        $this->assertFalse($this->validator->passes('', '10.0.0.1/24'));
    }
}
