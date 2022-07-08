<?php

namespace Tests\Unit\Listeners\V2\HostGroup;

use App\Events\V2\Task\Created;
use App\Listeners\V2\HostGroup\HostGroupEventSubscriber;
use App\Models\V2\HostGroup;
use App\Models\V2\ResourceTier;
use App\Models\V2\ResourceTierHostGroup;
use App\Services\V2\KingpinService;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class HostGroupEventSubscriberTest extends TestCase
{
    public HostGroupEventSubscriber $subscriber;

    public function setUp(): void
    {
        parent::setUp();

        $this->subscriber = new HostGroupEventSubscriber();

        $resourceTier = ResourceTier::factory()->create([
            'id' => 'rt-aaaaaaaa',
            'name' => 'Standard CPU',
            'availability_zone_id' => $this->availabilityZone()->id
        ]);

        // Set the default resource tier for the az
        $this->availabilityZone()->resourceTier()->associate($resourceTier)->save();

        HostGroup::factory()
            ->count(3)
            ->state(new Sequence(
                [
                    'id' => 'hg-standard-cpu-1',
                    'name' => 'I have the most free capacity',
                ],
                [
                    'id' => 'hg-standard-cpu-2',
                    'name' => 'I have less free capacity',
                ],
                [
                    'id' => 'hg-standard-cpu-3',
                    'name' => 'I have less free capacity',
                ],
            ))
            ->create([
                'availability_zone_id' => $this->availabilityZone()->id,
                'host_spec_id' => 'hs-standard-cpu',
            ])->each(function ($hostGroup) {
                ResourceTierHostGroup::factory()->create([
                    'resource_tier_id' => 'rt-aaaaaaaa',
                    'host_group_id' => $hostGroup->id
                ]);
            });

        $this->kingpinServiceMock()
            ->expects('post')
            ->withArgs([
                KingpinService::SHARED_HOST_GROUP_CAPACITY,
                [
                    'json' => [
                        'hostGroupIds' => [
                            'hg-standard-cpu-1',
                            'hg-standard-cpu-2',
                            'hg-standard-cpu-3'
                        ]
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    [
                        'hostGroupId' => 'hg-standard-cpu-2',
                        'cpuUsage' => 90,
                        'cpuUsedMHz' => 90,
                        'cpuCapacityMHz' => 100,
                        'ramUsage' => 90,
                        'ramUsedMB' => 900,
                        'ramCapacityMB' => 1000,
                    ],
                    [
                        'hostGroupId' => 'hg-standard-cpu-1',
                        'cpuUsage' => 25,
                        'cpuUsedMHz' => 25,
                        'cpuCapacityMHz' => 100,
                        'ramUsage' => 25,
                        'ramUsedMB' => 250,
                        'ramCapacityMB' => 1000,
                    ],
                    [
                        'hostGroupId' => 'hg-standard-cpu-3',
                        'cpuUsage' => 40,
                        'cpuUsedMHz' => 40,
                        'cpuCapacityMHz' => 100,
                        'ramUsage' => 40,
                        'ramUsedMB' => 400,
                        'ramCapacityMB' => 1000,
                    ]
                ]));
            });
    }

    public function testHostGroupIsSelectedForInstance()
    {
        Event::fake(Created::class);

        $this->instanceModel()->setAttribute('deploy_data', ['resource_tier_id' => 'rt-aaaaaaaa'])->saveQuietly();

        $task = $this->createSyncUpdateTask($this->instanceModel());

        $subscriber = new HostGroupEventSubscriber();

        $dispatcher = new Dispatcher();
        $dispatcher->subscribe($subscriber);
        $dispatcher->dispatch(new Created($task));





    }

    public function testInstanceAlreadyHasHostGroupIdSkips()
    {
        Event::fake(Created::class);

        $this->instanceModel()->setAttribute('deploy_data', ['resource_tier_id' => 'rt-aaaaaaaa'])->saveQuietly();

        $task = $this->createSyncUpdateTask($this->instanceModel());
        $subscriber = new HostGroupEventSubscriber();

        $dispatcher = new Dispatcher();
        $dispatcher->subscribe($subscriber);
        $dispatcher->dispatch(new Created($task));

        // TODO: mock not run
        
    }

    public function testNoResourceTierInDeployDataSelectsDefaultForAz()
    {
        $task = $this->createSyncUpdateTask($this->instanceModel());

        $subscriber = new HostGroupEventSubscriber();

        $dispatcher = new Dispatcher();
        $dispatcher->subscribe($subscriber);
        $dispatcher->dispatch(new Created($task));
    }
    



//    public function testSubscriberIsTriggered_()
//    {
//
////        Event::fake(Created::class);
//
////        $this->task = $this->createSyncDeleteTask($this->floatingIpResource);
//
//
//
//        $task = new Task([
//            'id' => 'sync-delete',
//            'name' => Sync::TASK_NAME_UPDATE,
//            'data' => null
//        ]);
//        $this->instanceModel()
//            ->setAttribute('deploy_data', ['resource_tier_id' => 'rt-aaaaaaaa'])
//            ->save();
//
//
//        $task->resource()->associate($this->instanceModel());
//        $task->save();
//
////        Event::dispatch()
//
//
//        Event::assertListening(
//            Created::class,
//            HostGroupEventSubscriber::class
//        );
//
//    }
}
