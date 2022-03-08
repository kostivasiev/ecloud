<?php

namespace Tests\V2\NetworkRulePort;

use App\Models\V2\NetworkPolicy;
use App\Models\V2\NetworkRule;
use App\Models\V2\NetworkRulePort;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class DeleteTest extends TestCase
{
    protected NetworkPolicy $networkPolicy;
    protected NetworkRule $networkRule;
    protected NetworkRulePort $networkRulePort;

    public function setUp(): void
    {
        parent::setUp();

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        $this->networkRule = NetworkRule::factory()->make([
            'id' => 'nr-test',
            'name' => 'nr-test',
        ]);

        $this->networkRule->networkRulePorts()->create([
            'id' => 'nrp-test',
            'name' => 'nrp-test',
            'protocol' => 'TCP',
            'source' => '443',
            'destination' => '555',
        ]);

        $this->networkPolicy()->networkRules()->save($this->networkRule);
    }

    public function testDelete()
    {
        Event::fake([\App\Events\V2\Task\Created::class]);
        $this->vpc()->advanced_networking = true;
        $this->vpc()->saveQuietly();

        $this->delete('/v2/network-rule-ports/nrp-test')
            ->assertStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class);
    }

    public function testDeletePortForDhcpRuleFails()
    {
        $this->networkRule->type = NetworkRule::TYPE_DHCP;
        $this->networkRule->save();

        $this->delete('/v2/network-rule-ports/nrp-test')
            ->assertStatus(403);
    }
}