<?php

namespace Tests\V2\NetworkRulePort;

use App\Models\V2\Network;
use App\Models\V2\NetworkPolicy;
use App\Models\V2\NetworkRule;
use App\Models\V2\NetworkRulePort;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    protected NetworkPolicy $networkPolicy;
    protected NetworkRule $networkRule;
    protected NetworkRulePort $networkRulePort;

    public function setUp(): void
    {
        parent::setUp();
        $this->network();

        $this->nsxServiceMock()->expects('patch')->times(3)
            ->withSomeOfArgs('/policy/api/v1/infra/domains/default/security-policies/np-test')
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });
        $this->nsxServiceMock()->expects('get')->times(3)
            ->withSomeOfArgs('policy/api/v1/infra/realized-state/status?intent_path=/infra/domains/default/security-policies/np-test')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(
                    [
                        'publish_status' => 'REALIZED'
                    ]
                ));
            });
        $this->nsxServiceMock()->expects('patch')->times(3)
            ->withSomeOfArgs('/policy/api/v1/infra/domains/default/groups/np-test')
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });
        $this->nsxServiceMock()->expects('get')->times(3)
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

    public function testGetCollection()
    {
        $this->get('v2/network-rule-ports', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => 'nrp-test',
            'network_rule_id' => 'nr-test',
            'protocol' => 'TCP',
            'source' => '443',
            'destination' => '555',
        ])->assertResponseStatus(200);
    }

    public function testGet()
    {
        $this->get('v2/network-rule-ports/nrp-test', [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ])->seeJson([
            'id' => 'nrp-test',
            'network_rule_id' => 'nr-test',
            'protocol' => 'TCP',
            'source' => '443',
            'destination' => '555',
        ])->assertResponseStatus(200);
    }
}
