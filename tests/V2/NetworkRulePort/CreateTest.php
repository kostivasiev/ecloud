<?php

namespace Tests\V2\NetworkRulePort;


use App\Events\V2\Task\Created;
use App\Models\V2\NetworkRule;
use App\Models\V2\NetworkRulePort;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CreateTest extends TestCase
{
    protected NetworkRule $networkRule;
    protected NetworkRulePort $networkRulePort;

    public function setUp(): void
    {
        parent::setUp();

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        $this->networkRule = factory(NetworkRule::class)->make([
            'id' => 'nr-test',
            'name' => 'nr-test',
        ]);

        $this->networkPolicy()->networkRules()->save($this->networkRule);
    }

    public function testCreate()
    {
        Event::fake([Created::class]);

        $this->post('/v2/network-rule-ports', [
            'network_rule_id' => 'nr-test',
            'protocol' => 'TCP',
            'source' => '443',
            'destination' => '555',
        ])->seeJsonStructure([
            'data' => [
                'id',
                'task_id'
            ]
        ])->seeInDatabase(
            'network_rule_ports',
            [
                'network_rule_id' => 'nr-test',
                'protocol' => 'TCP',
                'source' => '443',
                'destination' => '555',
            ],
            'ecloud'
        )->assertResponseStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class);
    }

    public function testCreatePortForDhcpRuleFails()
    {
        $this->networkRule->type = NetworkRule::TYPE_DHCP;
        $this->networkRule->save();

        $this->post('/v2/network-rule-ports', [
            'network_rule_id' => 'nr-test',
            'protocol' => 'TCP',
            'source' => '443',
            'destination' => '555',
        ])->assertResponseStatus(422);
    }

    public function testCreatePortForCatchallRuleFails()
    {
        $this->networkRule->type = NetworkRule::TYPE_CATCHALL;
        $this->networkRule->save();

        $this->post('/v2/network-rule-ports', [
            'network_rule_id' => 'nr-test',
            'protocol' => 'TCP',
            'source' => '443',
            'destination' => '555',
        ])->assertResponseStatus(422);
    }
}
