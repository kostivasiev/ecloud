<?php

namespace Tests\V2\NetworkRulePort;

use App\Models\V2\Network;
use App\Models\V2\NetworkPolicy;
use App\Models\V2\NetworkRule;
use App\Models\V2\NetworkRulePort;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CreateTest extends TestCase
{
    use DatabaseMigrations;

    protected NetworkRule $networkRule;
    protected NetworkRulePort $networkRulePort;

    public function setUp(): void
    {
        parent::setUp();

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        Model::withoutEvents(function () {
            $networkRule = factory(NetworkRule::class)->make([
                'id' => 'nr-test',
                'name' => 'nr-test',
            ]);

            $this->networkPolicy()->networkRules()->save($networkRule);
        });
    }

    public function testCreate()
    {
        $this->post('/v2/network-rule-ports', [
            'network_rule_id' => 'nr-test',
            'protocol' => 'TCP',
            'source' => '443',
            'destination' => '555',
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
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
    }
}
