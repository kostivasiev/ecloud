<?php

namespace Tests\V2\NetworkRulePort;

use App\Models\V2\Network;
use App\Models\V2\NetworkPolicy;
use App\Models\V2\NetworkRule;
use App\Models\V2\NetworkRulePort;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    protected NetworkPolicy $networkPolicy;
    protected NetworkRule $networkRule;
    protected NetworkRulePort $networkRulePort;

    public function setUp(): void
    {
        parent::setUp();
        $this->network();

        $this->nsxServiceMock()->expects('patch')->twice()
            ->withSomeOfArgs('/policy/api/v1/infra/domains/default/security-policies/np-test')
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });

        $this->nsxServiceMock()->expects('get')->twice()
            ->withSomeOfArgs('policy/api/v1/infra/realized-state/status?intent_path=/infra/domains/default/security-policies/np-test')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(
                    [
                        'publish_status' => 'REALIZED'
                    ]
                ));
            });

        $this->nsxServiceMock()->shouldReceive('patch')
            ->withSomeOfArgs('/policy/api/v1/infra/domains/default/groups/np-test')
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });

        $this->nsxServiceMock()->expects('get')->twice()
            ->withArgs(['policy/api/v1/infra/realized-state/status?intent_path=/infra/domains/default/groups/np-test'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(['publish_status' => 'REALIZED']));
            });

        $this->networkPolicy = factory(NetworkPolicy::class)->create([
            'id' => 'np-test',
            'network_id' => $this->network()->id,
        ]);
        $this->networkRule = factory(NetworkRule::class)->create([
            'id' => 'nr-test',
            'network_policy_id' => $this->networkPolicy->id,
        ]);
        $this->networkRulePort = factory(NetworkRulePort::class)->create([
            'id' => 'nrp-test',
            'network_rule_id' => $this->networkRule->id,
        ]);
    }

    public function testUpdate()
    {
        $this->patch('v2/network-rule-ports/nrp-test', [
            'source' => '3306',
            'destination' => '444',
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeInDatabase(
            'network_rule_ports',
            [
                'id' => 'nrp-test',
                'source' => '3306',
                'destination' => '444',
            ],
            'ecloud'
        )->assertResponseStatus(202);
    }
}