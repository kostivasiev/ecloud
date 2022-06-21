<?php

namespace App\Console\Commands\Instance;

use App\Console\Commands\Command;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\HostGroup;
use App\Models\V2\Instance;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use App\Services\V2\KingpinService;

class SetHostGroupToStandard extends Command
{
    protected $signature = 'instance:set-hostgroup {--T|test-run}';
    protected $description = 'Sets unassigned instances to standard hostgroup';

    public function handle()
    {
        Region::each(function ($region) {
            /** @var AvailabilityZone $availabilityZone */
            $availabilityZone = $region->availabilityZones()->first();
            $region->vpcs()->each(function (Vpc $vpc) use ($availabilityZone) {
                try {
                    $response = $availabilityZone->kingpinService()->get(
                        sprintf(KingpinService::GET_VPC_INSTANCES_URI, $vpc->id)
                    );
                } catch (\Exception $e) {
                    return;
                }

                // which of these hostgroups don't exist
                $hostGroupIds = $this->findMissingHostGroupIds(array_unique(
                    collect(
                        json_decode($response->getBody()->getContents())
                    )
                        ->pluck('hostGroupID')
                        ->toArray()
                ));

                // Create the missing hostGroups
                foreach ($hostGroupIds as $hostGroupId) {
                    HostGroup::withoutEvents(function () use ($hostGroupId, $vpc, $availabilityZone) {
                        HostGroup::factory()
                            ->create([
                                'id' => $hostGroupId,
                                'vpc_id' => $vpc->id,
                                'availability_zone_id' => $availabilityZone->id,
                                'host_spec_id' => '', // <--- need to determine this
                            ]);
                    });
                }
            });
        });
    }

    public function findMissingHostGroupIds(array $hostGroupIds): array
    {
        return array_diff(
            $hostGroupIds,
            HostGroup::find($hostGroupIds)->pluck('id')->toArray()
        );
    }
}