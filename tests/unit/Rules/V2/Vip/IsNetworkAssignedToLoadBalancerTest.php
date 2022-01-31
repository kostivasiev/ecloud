<?php
namespace Tests\unit\Rules\V2\Vip;

use App\Models\V2\Network;
use App\Rules\V2\Vip\IsNetworkAssignedToLoadBalancer;
use Illuminate\Database\Eloquent\Model;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\Mocks\Resources\VipMock;
use Tests\TestCase;

class IsNetworkAssignedToLoadBalancerTest extends TestCase
{
    use VipMock, LoadBalancerMock;

    protected IsNetworkAssignedToLoadBalancer $rule;

    public function setUp(): void
    {
        parent::setUp();

        $this->loadBalancerNetwork();

        $this->rule = new IsNetworkAssignedToLoadBalancer($this->loadBalancer()->id);
    }

    public function testNetworkAssignedToLoadBalancerPasses()
    {
        $this->assertTrue($this->rule->passes('network_id', $this->network()->id));
    }

    public function testNetworkNotAssignedToLoadBalancerFails()
    {
        $network = Model::withoutEvents(function () {
            return factory(Network::class)->create([
                'id' => 'net-' . uniqid(),
                'name' => 'test',
                'subnet' => '10.0.0.0/24',
                'router_id' => $this->router()->id
            ]);
        });

        $this->assertFalse($this->rule->passes('network_id', $network->id));
    }
}