<?php

namespace Tests\Unit\Jobs\NetworkPolicy;

use App\Events\V2\Task\Created;
use App\Jobs\NetworkPolicy\AllowLogicMonitor;
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

class AllowLogicMonitorTest extends TestCase
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

    public function testNoAdvancedNetworkingFails()
    {
        $this->getAdminClientMock();
        dispatch(new AllowLogicMonitor($this->networkPolicy(), $this->task));

        $networkRule = $this->networkPolicy()
            ->networkRules()
            ->where('name', '=', 'Collector_Rule')
            ->first();
        $this->assertNull($networkRule);
    }

    public function testNoCollectorFound()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $this->getAdminClientMock(true);
        dispatch(new AllowLogicMonitor($this->networkPolicy(), $this->task));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        $this->assertCount(0, $this->networkPolicy()->networkRules()->get()->toArray());
    }

    public function testNetworkRulesCreated()
    {
        $this->vpc()->setAttribute('advanced_networking', true)->saveQuietly();

        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);
        $this->getAdminClientMock();
        dispatch(new AllowLogicMonitor($this->networkPolicy(), $this->task));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        // Validate Rule and Ports have been created
        $networkRule = $this->networkPolicy()->networkRules()->first();
        $networkRulePorts = $networkRule->networkRulePorts()->get();

        $ruleConfig = config('network.rule_templates')[0];
        $this->assertEquals('123.123.123.123', $networkRule->source);
        $this->assertEquals($ruleConfig['name'], $networkRule->name);
        $this->assertEquals($ruleConfig['ports'][0]['protocol'], $networkRulePorts[0]->protocol);
        $this->assertEquals($ruleConfig['ports'][1]['destination'], $networkRulePorts[1]->destination);
        $this->assertEquals($ruleConfig['ports'][2]['destination'], $networkRulePorts[2]->destination);
        $this->assertEquals($ruleConfig['ports'][3]['destination'], $networkRulePorts[3]->destination);
    }


    /**
     * Gets AdminClient mock
     * @param bool $fails
     * @return AllowLogicMonitorTest
     */
    private function getAdminClientMock(bool $fails = false): AllowLogicMonitorTest
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
                                    'datacentreId' => 4,
                                    'datacentre' => 'MAN4',
                                    'ipAddress' => '123.123.123.123',
                                    'isShared' => true,
                                    'createdAt' => '2020-01-01T10:30:00+00:00',
                                    'updatedAt' => '2020-01-01T10:30:00+00:00'
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
