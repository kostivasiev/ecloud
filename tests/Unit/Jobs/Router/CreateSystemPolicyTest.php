<?php

namespace Tests\Unit\Jobs\Router;

use App\Events\V2\Task\Created;
use App\Jobs\Router\CreateSystemPolicy;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateSystemPolicyTest extends TestCase
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
            $this->task->resource()->associate($this->router());
            $this->task->save();
        });
    }

    public function testSystemPolicyExistsSkips()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $this->firewallPolicy()
            ->setAttribute('name', 'System')
            ->saveQuietly();

        dispatch(new CreateSystemPolicy($this->task));

        $this->task->refresh();

        Event::assertNotDispatched(JobFailed::class);

        Event::assertNotDispatched(Created::class, function ($event) {
            return $event->model->resource instanceof FirewallPolicy
                && $event->model->name == Sync::TASK_NAME_UPDATE;
        });

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testAddSystemPolicy()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        dispatch(new CreateSystemPolicy($this->task));

        Event::assertNotDispatched(JobFailed::class);

        $firewallPolicy = FirewallPolicy::where('router_id', '=', $this->router()->id)->first();
        $this->assertNotEmpty($firewallPolicy);
        $this->assertEquals('System', $firewallPolicy->name);
    }

    public function testSystemPolicyRulesCreated()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        dispatch(new CreateSystemPolicy($this->task));

        Event::assertNotDispatched(JobFailed::class);


        $firewallPolicy = $this->router()
            ->firewallPolicies()
            ->where('name', '=', 'System')
            ->first();

        $this->assertEquals(
            count(config('firewall.system.rules')),
            count($firewallPolicy->firewallRules()->get()->toArray())
        );

        foreach (config('firewall.system.rules') as $rule) {
            $firewallRule = $firewallPolicy->firewallRules()
                ->where([
                    ['name', '=', $rule['name']],
                    ['direction', '=', $rule['direction']]
                ])->first();
            $this->assertEquals($rule['name'], $firewallRule->name);
            $this->assertEquals($rule['action'], $firewallRule->action);
            $this->assertEquals($rule['sequence'], $firewallRule->sequence);
            $this->assertEquals($rule['direction'], $firewallRule->direction);
            $this->assertEquals($rule['destination'], $firewallRule->destination);

            foreach ($rule['ports'] as $port) {
                $firewallRulePort = $firewallRule->firewallRulePorts()
                    ->where([
                        ['protocol', '=', $port['protocol']],
                        ['destination', '=', $port['destination']],
                    ])->first();
                $this->assertEquals($port['protocol'], $firewallRulePort->protocol);
                $this->assertEquals($port['source'], $firewallRulePort->source);
                $this->assertEquals($port['destination'], $firewallRulePort->destination);
            }
        }
    }
}
