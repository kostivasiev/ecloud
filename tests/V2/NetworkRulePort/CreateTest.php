<?php

namespace Tests\V2\NetworkRulePort;


use App\Events\V2\NetworkPolicy\Saved;
use App\Events\V2\NetworkPolicy\Saving;
use App\Models\V2\NetworkRule;
use App\Models\V2\NetworkRulePort;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
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

        Model::withoutEvents(function () {
            $this->networkRule = factory(NetworkRule::class)->make([
                'id' => 'nr-test',
                'name' => 'nr-test',
            ]);

            $this->networkPolicy()->networkRules()->save($this->networkRule);
        });
    }

    public function testCreate()
    {
        Event::fake([Saving::class, Saved::class]);
        $this->post('/v2/network-rule-ports', [
            'network_rule_id' => 'nr-test',
            'protocol' => 'TCP',
            'source' => '443',
            'destination' => '555',
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

        Event::assertDispatched(Saving::class);
        Event::assertDispatched(Saved::class);
    }
}
