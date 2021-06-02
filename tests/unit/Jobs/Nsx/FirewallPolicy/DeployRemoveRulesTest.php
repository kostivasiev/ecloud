<?php

namespace Tests\unit\Jobs\Nsx\FirewallPolicy;

use App\Jobs\Nsx\FirewallPolicy\Deploy;
use App\Jobs\Nsx\FirewallPolicy\DeployRemoveRules;
use App\Models\V2\FirewallPolicy;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeployRemoveRulesTest extends TestCase
{
    protected FirewallPolicy $firewallPolicy;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testPolicyRemovesRuleIfExistsAndTrashed()
    {
        $this->firewallPolicy = factory(FirewallPolicy::class)->create([
            'id' => 'fwp-test',
            'router_id' => $this->router()->id,
        ]);

        $rule = $this->firewallPolicy->firewallRules()->create([
            'id' => 'test-rule-for-removal',
            'name' => 'test-rule-for-removal',
            'sequence' => 2,
            'source' => '192.168.1.1',
            'destination' => '192.168.1.2',
            'action' => 'REJECT',
            'direction' => 'IN',
            'enabled' => true,
        ]);
        $rule->delete();

        $this->nsxServiceMock()->expects('get')
            ->withArgs(['/policy/api/v1/infra/domains/default/gateway-policies/fwp-test/rules'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'rules' => [
                        [
                            'id' => 'test-rule-for-removal'
                        ]
                    ]
                ]));
            });

        $this->nsxServiceMock()->expects('delete')
            ->withArgs(['/policy/api/v1/infra/domains/default/gateway-policies/fwp-test/rules/test-rule-for-removal'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new DeployRemoveRules($this->firewallPolicy));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testPolicySkipRuleRemovalIfExistsAndNotTrashed()
    {
        $this->firewallPolicy = factory(FirewallPolicy::class)->create([
            'id' => 'fwp-test',
            'router_id' => $this->router()->id,
        ]);

        $rule = $this->firewallPolicy->firewallRules()->create([
            'id' => 'test-rule-for-removal',
            'name' => 'test-rule-for-removal',
            'sequence' => 2,
            'source' => '192.168.1.1',
            'destination' => '192.168.1.2',
            'action' => 'REJECT',
            'direction' => 'IN',
            'enabled' => true,
        ]);

        $this->nsxServiceMock()->expects('get')
            ->withArgs(['/policy/api/v1/infra/domains/default/gateway-policies/fwp-test/rules'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'rules' => [
                        [
                            'id' => 'test-rule-for-removal'
                        ]
                    ]
                ]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new DeployRemoveRules($this->firewallPolicy));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testPolicySkipRuleRemovalIfNotExists()
    {
        $this->firewallPolicy = factory(FirewallPolicy::class)->create([
            'id' => 'fwp-test',
            'router_id' => $this->router()->id,
        ]);

        $this->nsxServiceMock()->expects('get')
            ->withArgs(['/policy/api/v1/infra/domains/default/gateway-policies/fwp-test/rules'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'rules' => [
                        [
                            'id' => 'unknown-rule'
                        ]
                    ]
                ]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new DeployRemoveRules($this->firewallPolicy));

        Event::assertNotDispatched(JobFailed::class);
    }
}
