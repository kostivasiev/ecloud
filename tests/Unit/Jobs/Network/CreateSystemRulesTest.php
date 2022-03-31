<?php

namespace Tests\Unit\Jobs\Network;

use App\Events\V2\Task\Created;
use App\Jobs\Network\CreateSystemRules;
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

class CreateSystemRulesTest extends TestCase
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
            $this->task->resource()->associate($this->network());
            $this->task->save();
        });
    }

    public function testNoCollectorFound()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);
        $this->firewallPolicy()->setAttribute('name', 'System')->saveQuietly();
        $this->getAdminClientMock(true);
        dispatch(new CreateSystemRules($this->task));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        $this->assertCount(0, $this->firewallPolicy()->firewallRules()->get()->toArray());
    }

    public function testFirewallRuleCreated()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);
        $this->firewallPolicy()->setAttribute('name', 'System')->saveQuietly();
        $this->getAdminClientMock();
        dispatch(new CreateSystemRules($this->task));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        $firewallRule = $this->firewallPolicy()->firewallRules()->first();
        $firewallRulePorts = $firewallRule->firewallRulePorts()->get();

        $this->assertEquals(config('firewall.system.rules')[0]['name'], $firewallRule->name);
        $this->assertEquals(config('firewall.system.rules')[0]['ports'][0]['protocol'], $firewallRulePorts[0]->protocol);
        $this->assertEquals(config('firewall.system.rules')[0]['ports'][1]['destination'], $firewallRulePorts[1]->destination);
        $this->assertEquals(config('firewall.system.rules')[0]['ports'][2]['destination'], $firewallRulePorts[2]->destination);
        $this->assertEquals(config('firewall.system.rules')[0]['ports'][3]['destination'], $firewallRulePorts[3]->destination);
    }

    /**
     * Gets AdminClient mock
     * @param bool $fails
     * @return CreateSystemRulesTest
     */
    private function getAdminClientMock(bool $fails = false): CreateSystemRulesTest
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
