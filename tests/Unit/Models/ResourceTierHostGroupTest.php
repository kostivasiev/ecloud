<?php

namespace Tests\Unit\Models;


use App\Models\V2\AvailabilityZone;
use App\Models\V2\HostGroup;
use App\Models\V2\ResourceTier;
use App\Models\V2\ResourceTierHostGroup;
use App\Services\V2\KingpinService;
use Database\Seeders\ResourceTierSeeder;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Tests\TestCase;

class ResourceTierHostGroupTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->availabilityZone = AvailabilityZone::factory()->create([
            'id' => 'az-aaaaaaaa',
            'region_id' => $this->region()->id,
        ]);

        (new ResourceTierSeeder())->run();
    }

    public function testRelationships()
    {
        $resourceTierStandardCpu = ResourceTier::find('rt-aaaaaaaa');

        $resourceTierHostGroup = $resourceTierStandardCpu->resourceTierHostGroups->first();

        // pivot -> host group
        $this->assertEquals('hg-99f9b758', $resourceTierHostGroup->hostGroup->id);

        // pivot -> resource tier
        $this->assertEquals('rt-aaaaaaaa', $resourceTierHostGroup->resourceTier->id);

        // resource tier -> through pivot -> host groups
        $this->assertEquals('hg-99f9b758', $resourceTierStandardCpu->hostGroups->first()->id);
    }

    public function testGetDefaultHostGroupByCapacity_NO_MAPPING()
    {
        $resourceTier = ResourceTier::factory()->create([
            'availability_zone_id' => $this->availabilityZone()->id
        ]);

        // Set the default resource tier for the az
        $this->availabilityZone()->resourceTier()->associate($resourceTier)->save();

        HostGroup::factory()
            ->count(5)
            ->state(new Sequence(
                ['id' => 'hg-1'],
                ['id' => 'hg-2'],
                ['id' => 'hg-3'],
                ['id' => 'hg-4'],
                ['id' => 'hg-5'],
            ))
            ->create([
                'availability_zone_id' => $this->availabilityZone()->id,
                'host_spec_id' => 'hs-standard-cpu',
            ])->each(function ($hostGroup) use ($resourceTier) {
                ResourceTierHostGroup::factory()->create([
                    'resource_tier_id' => $resourceTier->id,
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
                            'hg-1',
                            'hg-2',
                            'hg-3',
                            'hg-4',
                            'hg-5',
                        ]
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    [
                        'hostGroupId' => 'hg-1',
                        'cpuUsage' => 90,
                        'cpuUsedMHz' => 90,
                        'cpuCapacityMHz' => 100,
                        'ramUsage' => 90,
                        'ramUsedMB' => 900,
                        'ramCapacityMB' => 1000,
                    ],
                    [
                        'hostGroupId' => 'hg-5',
                        'cpuUsage' => 50,
                        'cpuUsedMHz' => 50,
                        'cpuCapacityMHz' => 100,
                        'ramUsage' => 50,
                        'ramUsedMB' => 500,
                        'ramCapacityMB' => 1000,
                    ],
                    [
                        'hostGroupId' => 'hg-4',
                        'cpuUsage' => 60,
                        'cpuUsedMHz' => 60,
                        'cpuCapacityMHz' => 100,
                        'ramUsage' => 60,
                        'ramUsedMB' => 600,
                        'ramCapacityMB' => 1000,
                    ],
                    [
                        'hostGroupId' => 'hg-2',
                        'cpuUsage' => 80,
                        'cpuUsedMHz' => 80,
                        'cpuCapacityMHz' => 100,
                        'ramUsage' => 80,
                        'ramUsedMB' => 800,
                        'ramCapacityMB' => 1000,
                    ],
                    [
                        'hostGroupId' => 'hg-3',
                        'cpuUsage' => 70,
                        'cpuUsedMHz' => 70,
                        'cpuCapacityMHz' => 100,
                        'ramUsage' => 70,
                        'ramUsedMB' => 700,
                        'ramCapacityMB' => 1000,
                    ]
                ]));
            });

        $this->assertEquals([
            0 => 'hg-5',
            1 => 'hg-4',
            2 => 'hg-3',
            3 => 'hg-2',
            4 => 'hg-1',
        ], $resourceTier->getHostGroupCapacities()->pluck('id')->toArray());

        // Get the host group with the least utilised capacity (hg-5 @ 50%)
        $this->assertEquals('hg-5', $resourceTier->getHostGroupCapacities()->first()['id']);

        $leastUtilisedHostGroup = $resourceTier->getDefaultHostGroup();
        $this->assertNotNull($leastUtilisedHostGroup);
        $this->assertEquals('hg-5', $leastUtilisedHostGroup->id);
    }

    public function testGetDefaultHostGroupByCapacity_WITH_MAPPING()
    {
        // Mapping until we rename the clusters
        config(['host-group-map' => [
            'hg-1' => 'hg-1-CURRENT-CLUSTER-NAME',
            'hg-2' => 'hg-2-CURRENT-CLUSTER-NAME',
            'hg-3' => 'hg-3-CURRENT-CLUSTER-NAME',
            'hg-4' => 'hg-4-CURRENT-CLUSTER-NAME',
            'hg-5' => 'hg-5-CURRENT-CLUSTER-NAME',
        ]]);

        $resourceTier = ResourceTier::factory()->create([
            'availability_zone_id' => $this->availabilityZone()->id
        ]);

        // Set the default resource tier for the az
        $this->availabilityZone()->resourceTier()->associate($resourceTier)->save();

        HostGroup::factory()
            ->count(5)
            ->state(new Sequence(
                ['id' => 'hg-1'],
                ['id' => 'hg-2'],
                ['id' => 'hg-3'],
                ['id' => 'hg-4'],
                ['id' => 'hg-5'],
            ))
            ->create([
                'availability_zone_id' => $this->availabilityZone()->id,
                'host_spec_id' => 'hs-standard-cpu',
            ])->each(function ($hostGroup) use ($resourceTier) {
                ResourceTierHostGroup::factory()->create([
                    'resource_tier_id' => $resourceTier->id,
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
                        // ID's replaced with current cluster id/names
                        'hostGroupIds' => [
                            'hg-1-CURRENT-CLUSTER-NAME',
                            'hg-2-CURRENT-CLUSTER-NAME',
                            'hg-3-CURRENT-CLUSTER-NAME',
                            'hg-4-CURRENT-CLUSTER-NAME',
                            'hg-5-CURRENT-CLUSTER-NAME',
                        ]
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    [
                        'hostGroupId' => 'hg-1-CURRENT-CLUSTER-NAME',
                        'cpuUsage' => 90,
                        'cpuUsedMHz' => 90,
                        'cpuCapacityMHz' => 100,
                        'ramUsage' => 90,
                        'ramUsedMB' => 900,
                        'ramCapacityMB' => 1000,
                    ],
                    [
                        'hostGroupId' => 'hg-5-CURRENT-CLUSTER-NAME',
                        'cpuUsage' => 50,
                        'cpuUsedMHz' => 50,
                        'cpuCapacityMHz' => 100,
                        'ramUsage' => 50,
                        'ramUsedMB' => 500,
                        'ramCapacityMB' => 1000,
                    ],
                    [
                        'hostGroupId' => 'hg-4-CURRENT-CLUSTER-NAME',
                        'cpuUsage' => 60,
                        'cpuUsedMHz' => 60,
                        'cpuCapacityMHz' => 100,
                        'ramUsage' => 60,
                        'ramUsedMB' => 600,
                        'ramCapacityMB' => 1000,
                    ],
                    [
                        'hostGroupId' => 'hg-2-CURRENT-CLUSTER-NAME',
                        'cpuUsage' => 80,
                        'cpuUsedMHz' => 80,
                        'cpuCapacityMHz' => 100,
                        'ramUsage' => 80,
                        'ramUsedMB' => 800,
                        'ramCapacityMB' => 1000,
                    ],
                    [
                        'hostGroupId' => 'hg-3-CURRENT-CLUSTER-NAME',
                        'cpuUsage' => 70,
                        'cpuUsedMHz' => 70,
                        'cpuCapacityMHz' => 100,
                        'ramUsage' => 70,
                        'ramUsedMB' => 700,
                        'ramCapacityMB' => 1000,
                    ]
                ]));
            });

        // current cluster names / ID's swapped back to host group ids
        $this->assertEquals([
            0 => 'hg-5',
            1 => 'hg-4',
            2 => 'hg-3',
            3 => 'hg-2',
            4 => 'hg-1',
        ], $resourceTier->getHostGroupCapacities()->pluck('id')->toArray());

        // Get the host group with the least utilised capacity (hg-5 @ 50%)
        $this->assertEquals('hg-5', $resourceTier->getHostGroupCapacities()->first()['id']);

        $leastUtilisedHostGroup = $resourceTier->getDefaultHostGroup();
        $this->assertNotNull($leastUtilisedHostGroup);
        $this->assertEquals('hg-5', $leastUtilisedHostGroup->id);
    }
}
