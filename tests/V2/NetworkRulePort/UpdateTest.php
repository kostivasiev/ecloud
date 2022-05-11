<?php

namespace Tests\V2\NetworkRulePort;

use App\Models\V2\NetworkRule;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class UpdateTest extends TestCase
{
    protected $networkRule;

    public function setUp(): void
    {
        parent::setUp();

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        $this->networkRule = NetworkRule::factory()->make([
            'id' => 'nr-test-1',
            'name' => 'nr-test-1',
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

    public function testUpdate()
    {
        Event::fake(\App\Events\V2\Task\Created::class);
        $this->vpc()->advanced_networking = true;
        $this->vpc()->saveQuietly();

        $this->patch('v2/network-rule-ports/nrp-test', [
            'source' => '3306',
            'destination' => '444',
        ])->assertStatus(202);

        $this->assertDatabaseHas(
            'network_rule_ports',
            [
                'id' => 'nrp-test',
                'source' => '3306',
                'destination' => '444',
            ],
            'ecloud'
        );

        Event::assertDispatched(\App\Events\V2\Task\Created::class);
    }

    public function testUpdatePortForDhcpRuleFails()
    {
        $this->networkRule->type = NetworkRule::TYPE_DHCP;
        $this->networkRule->save();

        $this->patch('v2/network-rule-ports/nrp-test', [
            'source' => '3306',
            'destination' => '444',
        ])->assertStatus(403);
    }
}