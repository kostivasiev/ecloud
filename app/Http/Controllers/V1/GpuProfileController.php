<?php

namespace App\Http\Controllers\V1;

use App\Models\V1\GpuProfile;
use App\Models\V1\VirtualMachine;
use UKFast\DB\Ditto\QueryTransformer;

use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\Api\Resource\Traits\RequestHelper;

use Illuminate\Http\Request;

class GpuProfileController extends BaseController
{
    use ResponseHelper, RequestHelper;

    private static $model = GpuProfile::class;

    /**
     * Show collection
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $collectionQuery = static::getQuery();

        (new QueryTransformer($request))
            ->config(self::$model)
            ->transform($collectionQuery);

        $items = $collectionQuery->paginate($this->perPage);

        return $this->respondCollection(
            $request,
            $items
        );
    }

    /**
     * Show item
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\http\Response
     */
    public function show(Request $request, $id)
    {
        return $this->respondItem($request, static::getById($request, $id));
    }

    /**
     * Get item by ID
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public static function getById(Request $request, $id)
    {
        $object = static::getQuery($request)->findOrFail($id);

        return $object;
    }

    /**
     * Return a query builder for the model
     * @return mixed
     */
    public static function getQuery()
    {
        return self::$model::query();
    }

    /**
     * Calculates available GPU resources in pool for assigning to VM's
     * @return int
     */
    public static function gpuResourcePoolAvailability()
    {
        $available = GpuProfile::CARDS_AVAILABLE;

        $profiles = GpuProfile::select('uuid', 'profile_name')->get()->pluck('uuid', 'profile_name');
        $profiles = $profiles->flip();

        // Get a list of active VM's with a GPU profile assigned
        $vms = VirtualMachine::query()
            ->where('servers_ecloud_gpu_profile_uuid', '!=', '0')
            ->where('servers_active', '=', 'y')
            ->whereNotNull('servers_ecloud_gpu_profile_uuid')
            ->pluck('servers_ecloud_gpu_profile_uuid', 'servers_id')
        ->toArray();

        foreach ($vms as $vmId => $profileUuid) {
            if (!in_array($profiles[$profileUuid], array_keys(GpuProfile::CARD_PROFILES))) {
                Log::error(
                    'Unrecognised GPU profile \'' . $profileUuid . '\' found on Virtual Machine # ' . $vmId .' when calculating GPU pool availability'
                );
                continue;
            }

            $available -= GpuProfile::CARD_PROFILES[$profiles[$profileUuid]];
        }

        return $available;
    }
}
