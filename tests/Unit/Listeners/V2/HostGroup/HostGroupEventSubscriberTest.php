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
use Tests\TestCase;

class HostGroupEventSubscriberTest extends TestCase
{
    public $subscriber;

    public function setUp(): void
    {
        parent::setUp();

        $this->subscriber = \Mockery::mock(HostGroupEventSubscriber::class)->shouldAllowMockingProtectedMethods()->makePartial();

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
            ->zeroOrMoreTimes()
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
        $this->instanceModel()->setAttribute('host_group_id', null)->save();

        $this->instanceModel()->setAttribute('deploy_data', ['resource_tier_id' => 'rt-aaaaaaaa'])->saveQuietly();

        $task = $this->createSyncUpdateTask($this->instanceModel());

        $this->subscriber->handleTaskCreatedEvent(new Created($task));

        $this->instanceModel()->refresh();

        $this->assertEquals('hg-standard-cpu-1', $this->instanceModel()->hostGroup->id);
    }

    public function testInstanceAlreadyHasHostGroupIdSkips()
    {
        $this->instanceModel()->setAttribute('host_group_id', $this->hostGroup()->id)->saveQuietly();

        $task = $this->createSyncUpdateTask($this->instanceModel());

        $this->subscriber->expects('assignToInstance')->never();

        $this->subscriber->handleTaskCreatedEvent(new Created($task));

        $this->assertEquals($this->hostGroup()->id, $this->instanceModel()->host_group_id);
    }

    public function testNoResourceTierInDeployDataSelectsDefaultForAz()
    {
        $resourceTier = $this->subscriber->getResourceTier($this->instanceModel());
        $this->assertEquals('rt-aaaaaaaa', $resourceTier->id);
    }
}
