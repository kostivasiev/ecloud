<?php

namespace Tests\V2\NetworkRulePort;

use App\Models\V2\NetworkRule;
use App\Models\V2\NetworkRulePort;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class GetTest extends TestCase
{
    protected NetworkRule $networkRule;
    protected NetworkRulePort $networkRulePort;

    public function setUp(): void
    {
        parent::setUp();

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        Model::withoutEvents(function () {
            $networkRule = NetworkRule::factory()->make([
                'id' => 'nr-test',
                'name' => 'nr-test',
            ]);

            $networkRule->networkRulePorts()->create([
                'id' => 'nrp-test',
                'name' => 'nrp-test',
                'protocol' => 'TCP',
                'source' => '443',
                'destination' => '555',
            ]);

            $this->networkPolicy()->networkRules()->save($networkRule);
        });
    }

    public function testGetCollection()
    {
        $this->get('v2/network-rule-ports')
            ->assertJsonFragment([
                'id' => 'nrp-test',
                'network_rule_id' => 'nr-test',
                'protocol' => 'TCP',
                'source' => '443',
                'destination' => '555',
            ])->assertStatus(200);
    }

    public function testGet()
    {
        $this->get('v2/network-rule-ports/nrp-test')
            ->assertJsonFragment([
                'id' => 'nrp-test',
                'network_rule_id' => 'nr-test',
                'protocol' => 'TCP',
                'source' => '443',
                'destination' => '555',
            ])->assertStatus(200);
    }

    public function testGetHiddenNotAdminFails()
    {
        $this->router()->setAttribute('is_management', true)->save();

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        $this->get('/v2/network-rule-ports/nrp-test')
            ->assertStatus(404);
    }

    public function testGetHiddenAdminPasses()
    {
        $this->router()->setAttribute('is_management', true)->save();

        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        $this->get('/v2/network-rule-ports/nrp-test')->assertStatus(200);
    }
}
