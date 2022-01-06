<?php

namespace Tests\unit\Jobs\Nsx\FirewallPolicy;

use App\Events\V2\FirewallRule\Deleted;
use App\Jobs\Nsx\FirewallPolicy\Undeploy;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Models\V2\Task;
use App\Support\Sync;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UndeployTest extends TestCase
{
    protected Task $task;
    protected FirewallRule $firewallRule;
    protected FirewallRulePort $firewallRulePort;

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

    public function testPolicyRemovedAndRulesDeleted()
    {
        Model::withoutEvents(function () {
            $this->firewallRule = $this->firewallPolicy()->firewallRules()->create([
                'id' => 'fwr-test-1',
                'name' => 'fwr-test-1',
                'sequence' => 2,
                'source' => '192.168.1.1',
                'destination' => '192.168.1.2',
                'action' => 'REJECT',
                'direction' => 'IN',
                'enabled' => true,
            ]);

            $this->firewallRulePort = $this->firewallRule->firewallRulePorts()->create([
                'id' => 'fwrp-test',
                'name' => 'fwrp-test',
                'protocol' => 'TCP',
                'source' => 'ANY',
                'destination' => '80'
            ]);
        });

        $this->nsxServiceMock()->shouldReceive('delete')
            ->withArgs([
                '/policy/api/v1/infra/domains/default/gateway-policies/fwp-test',
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });

        Event::fake([JobFailed::class, Deleted::class]);

        dispatch(new Undeploy($this->task));

        Event::assertNotDispatched(JobFailed::class);

        $this->firewallRule->refresh();
        $this->assertNotNull($this->firewallRule->deleted_at);
        $this->firewallRulePort->refresh();
        $this->assertNotNull($this->firewallRulePort->deleted_at);
    }
}
