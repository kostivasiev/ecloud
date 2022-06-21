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
        // todo: start with instances...
        Instance::where(function ($query) {
            $query->whereNull('host_group_id');
            $query->orWhere('host_group_id', '=', '');
        })->each(function (Instance $instance) {
            $hostGroupId = $instance->getHostGroupId();
            $hostGroup = HostGroup::find($instance->getHostGroupId());
            if (!$hostGroup) {
                $this->info('Creating hostgroup `' . $hostGroupId . '`');
                $hostGroup = HostGroup::withoutEvents(function () use ($instance, $hostGroupId) {
                    HostGroup::factory()
                        ->create([
                            'id' => $hostGroupId,
                            'vpc_id' => $instance->vpc->id,
                            'availability_zone_id' => $instance->availabilityZone->id,
                            'host_spec_id' => 'hs-test', // <--- need to determine this
                            'windows_enabled' => '', // <--- need to determine this too, get from instance platform attribute?
                        ]);
                });
            }
        });



//        Region::each(function ($region) {
//            /** @var AvailabilityZone $availabilityZone */
//            $availabilityZone = $region->availabilityZones()->first();
//            $region->vpcs()->each(function (Vpc $vpc) use ($availabilityZone) {
//                $hostGroupIds = $this->getMissingHostGroupIds($availabilityZone, $vpc);
//
//                // Create the missing hostGroups
//                foreach ($hostGroupIds as $hostGroupId) {
//                    $this->info('Creating hostgroup `' . $hostGroupId . '`');
//                    $hostGroup = HostGroup::withoutEvents(function () use ($hostGroupId, $vpc, $availabilityZone) {
//                        HostGroup::factory()
//                            ->create([
//                                'id' => $hostGroupId,
//                                'vpc_id' => $vpc->id,
//                                'availability_zone_id' => $availabilityZone->id,
//                                'host_spec_id' => 'hs-test', // <--- need to determine this
//                                'windows_enabled' => '', // <--- need to determine this too, get from instance platform attribute?
//                            ]);
//                    });
////                    dd($hostGroup->getAttributes());
//                }
//            });
//        });

    }

//    public function getMissingHostGroupIds(AvailabilityZone $availabilityZone, Vpc $vpc): array
//    {
//        // get hostgroup instances from nsx
//        try {
//            $response = $availabilityZone->kingpinService()->get(
//                sprintf(KingpinService::GET_VPC_INSTANCES_URI, $vpc->id)
//            );
//        } catch (\Exception $e) {
//            return [];
//        }
//
//        // extract just the hostGroupID
//        $hostGroupIds = array_unique(
//            collect(
//                json_decode($response->getBody()->getContents())
//            )
//                ->pluck('hostGroupID')
//                ->toArray()
//        );
//
//        // return only those ids that do not exist on the system yet
//        return $this->extractMissingHostGroupIds($hostGroupIds);
//    }
//
//    public function extractMissingHostGroupIds(array $hostGroupIds): array
//    {
//        return array_diff(
//            $hostGroupIds,
//            HostGroup::find($hostGroupIds)->pluck('id')->toArray()
//        );
//    }
}