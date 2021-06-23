<?php

namespace Tests\unit\Jobs\Nsx\NetworkPolicy;

use App\Jobs\Nsx\NetworkPolicy\Deploy;
use App\Jobs\Nsx\NetworkPolicy\UndeployTrashedRules;
use App\Models\V2\NetworkPolicy;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UndeployTrashedRulesTest extends TestCase
{
    protected NetworkPolicy $networkPolicy;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testPolicyRemovesRuleIfExistsAndTrashed()
    {
        $this->networkPolicy = factory(NetworkPolicy::class)->create([
            'id' => 'np-test',
            'network_id' => $this->network()->id,
        ]);

        $rule = $this->networkPolicy->networkRules()->create([
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
            ->withArgs(['/policy/api/v1/infra/domains/default/security-policies/np-test/rules'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'id' => 'test-rule-for-removal'
                        ]
                    ]
                ]));
            });

        $this->nsxServiceMock()->expects('delete')
            ->withArgs(['/policy/api/v1/infra/domains/default/security-policies/np-test/rules/test-rule-for-removal'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new UndeployTrashedRules($this->networkPolicy));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testPolicySkipRuleRemovalIfExistsAndNotTrashed()
    {
        $this->networkPolicy = factory(NetworkPolicy::class)->create([
            'id' => 'np-test',
            'network_id' => $this->network()->id,
        ]);

        $rule = $this->networkPolicy->networkRules()->create([
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
            ->withArgs(['/policy/api/v1/infra/domains/default/security-policies/np-test/rules'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'id' => 'test-rule-for-removal'
                        ]
                    ]
                ]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new UndeployTrashedRules($this->networkPolicy));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testPolicySkipRuleRemovalIfNotExists()
    {
        $this->networkPolicy = factory(NetworkPolicy::class)->create([
            'id' => 'np-test',
            'network_id' => $this->network()->id,
        ]);

        $this->nsxServiceMock()->expects('get')
            ->withArgs(['/policy/api/v1/infra/domains/default/security-policies/np-test/rules'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'id' => 'unknown-rule'
                        ]
                    ]
                ]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new UndeployTrashedRules($this->networkPolicy));

        Event::assertNotDispatched(JobFailed::class);
    }
}
