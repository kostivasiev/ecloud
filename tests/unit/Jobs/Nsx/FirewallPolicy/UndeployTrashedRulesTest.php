<?php

namespace Tests\unit\Jobs\Nsx\FirewallPolicy;

use App\Jobs\Nsx\FirewallPolicy\Deploy;
use App\Jobs\Nsx\FirewallPolicy\UndeployTrashedRules;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\Task;
use App\Support\Sync;
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
    protected Task $task;

    public function setUp(): void
    {
        parent::setUp();

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->firewallPolicy());
            $this->task->save();
        });
    }

    public function testPolicyRemovesRuleIfExistsAndTrashed()
    {
        $rule = $this->firewallPolicy()->firewallRules()->create([
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
                    'results' => [
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

        dispatch(new UndeployTrashedRules($this->task));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testPolicySkipRuleRemovalIfExistsAndNotTrashed()
    {
        $rule = $this->firewallPolicy()->firewallRules()->create([
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
                    'results' => [
                        [
                            'id' => 'test-rule-for-removal'
                        ]
                    ]
                ]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new UndeployTrashedRules($this->task));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testPolicySkipRuleRemovalIfNotExists()
    {
        $this->nsxServiceMock()->expects('get')
            ->withArgs(['/policy/api/v1/infra/domains/default/gateway-policies/fwp-test/rules'])
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

        dispatch(new UndeployTrashedRules($this->task));

        Event::assertNotDispatched(JobFailed::class);
    }
}
