<?php

namespace Tests\Unit\Jobs\Router;

use App\Events\V2\Task\Created;
use App\Jobs\Router\CreateCollectorRules;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Admin\Monitoring\AdminClient;
use UKFast\Admin\Monitoring\AdminCollectorClient;
use UKFast\Admin\Monitoring\Entities\Collector;

class CreateCollectorRulesTest extends TestCase
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
        $this->firewallPolicy()
            ->setAttribute('name', 'System')
            ->saveQuietly();

        app()->bind(FirewallPolicy::class, function () {
            return $this->firewallPolicy();
        });
    }

    public function testSkipIfManagementResource()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);
        $this->router()->setAttribute('is_management', true)->saveQuietly();
        $this->getAdminClientMock(true);
        dispatch(new CreateCollectorRules($this->task));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        $this->assertCount(0, $this->firewallPolicy()->firewallRules()->get()->toArray());
    }

    public function testSkipsIfNoSystemPolicy()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);
        $this->firewallPolicy()->setAttribute('name', 'Not System')->saveQuietly();
        $this->getAdminClientMock();
        dispatch(new CreateCollectorRules($this->task));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        $this->assertCount(0, $this->firewallPolicy()->firewallRules()->get()->toArray());
    }

    public function testNoCollectorFound()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);
        $this->getAdminClientMock(true);
        dispatch(new CreateCollectorRules($this->task));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        $this->assertCount(0, $this->firewallPolicy()->firewallRules()->get()->toArray());
    }

    public function testFirewallRuleCreated()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);
        $this->getAdminClientMock();
        dispatch(new CreateCollectorRules($this->task));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        $firewallRule = $this->firewallPolicy()->firewallRules()->first();
        $firewallRulePorts = $firewallRule->firewallRulePorts()->get();

        $firewallRules = config('firewall.rule_templates');
        $this->assertEquals($firewallRules[0]['name'], $firewallRule->name);
        $this->assertEquals($firewallRules[0]['ports'][0]['protocol'], $firewallRulePorts[0]->protocol);
        $this->assertEquals($firewallRules[0]['ports'][1]['destination'], $firewallRulePorts[1]->destination);
        $this->assertEquals($firewallRules[0]['ports'][2]['destination'], $firewallRulePorts[2]->destination);
        $this->assertEquals($firewallRules[0]['ports'][3]['destination'], $firewallRulePorts[3]->destination);
    }

    /**
     * Gets AdminClient mock
     * @param bool $fails
     * @return CreateCollectorRulesTest
     */
    private function getAdminClientMock(bool $fails = false): CreateCollectorRulesTest
    {
        app()->bind(AdminClient::class, function () use ($fails) {
            $mock = \Mockery::mock(AdminClient::class)->makePartial();
            $mock->allows('setResellerId')
                ->andReturnSelf();
            $mock->allows('collectors')
                ->andReturnUsing(function () use ($fails) {
                    $collectorsMock = \Mockery::mock(AdminCollectorClient::class)->makePartial();
                    $collectorsMock->allows('getAll')
                        ->once()
                        ->andReturnUsing(function () use ($fails) {
                            if ($fails) {
                                return [];
                            }
                            return [
                                new Collector([
                                    'name' => 'Collector Display Name',
                                    'datacentre_id' => 4,
                                    'datacentre' => 'MAN4',
                                    'ip_address' => '123.123.123.123',
                                    'is_shared' => true,
                                    'created_at' => '2020-01-01T10:30:00+00:00',
                                    'updated_at' => '2020-01-01T10:30:00+00:00'
                                ])
                            ];
                        });
                    return $collectorsMock;
                });
            return $mock;
        });
        return $this;
    }
}
