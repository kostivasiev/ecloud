<?php
namespace Tests\unit\Rules;

use App\Models\V2\NetworkPolicy;
use App\Models\V2\Network;
use App\Rules\V2\NetworkHasNoPolicy;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class NetworkHasNoAclTest extends TestCase
{
    use DatabaseMigrations;

    protected NetworkHasNoPolicy $rule;
    protected Network $network;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new NetworkHasNoPolicy();
        $this->availabilityZone();
        $this->network = factory(Network::class)->create([
            'router_id' => $this->router()->id,
        ]);
    }

    public function testRulePasses()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->assertTrue($this->rule->passes('', $this->network->id));
    }

    public function testRuleFails()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        factory(NetworkPolicy::class)->create([
            'network_id' => $this->network->id,
            'vpc_id' => $this->vpc()->id,
        ]);
        $this->assertFalse($this->rule->passes('', $this->network->id));
    }
}