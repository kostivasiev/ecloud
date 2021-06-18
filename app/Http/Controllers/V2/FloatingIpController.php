<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\FloatingIp\AssignRequest;
use App\Http\Requests\V2\FloatingIp\CreateRequest;
use App\Http\Requests\V2\FloatingIp\UpdateRequest;
use App\Models\V2\FloatingIp;
use App\Models\V2\Nat;
use App\Models\V2\Task;
use App\Resources\V2\FloatingIpResource;
use App\Resources\V2\TaskResource;
use App\Support\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class InstanceController
 * @package App\Http\Controllers\V2
 */
class FloatingIpController extends BaseController
{
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        // "resource_id" filtering hack - start
        if ($request->has('resource_id:eq')) {
            if ($request->get('resource_id:eq') === 'null') {
                $floatingIpIds = FloatingIp::forUser($request->user())->get()
                    ->reject(function ($floatingIp) {
                        return $floatingIp->resource_id != null;
                    })
                    ->map(function ($floatingIp) {
                        return $floatingIp->id;
                    });
                $collection = FloatingIp::whereIn('id', $floatingIpIds);
            } else {
                $resourceId = $request->get('resource_id:eq');
                $floatingIpIds = FloatingIp::forUser($request->user())->get()
                    ->reject(function ($floatingIp) use ($resourceId) {
                        return $floatingIp->resource_id != $resourceId;
                    })
                    ->map(function ($floatingIp) {
                        return $floatingIp->id;
                    });
                $collection = FloatingIp::whereIn('id', $floatingIpIds);
            }
            $request->query->remove('resource_id:eq');  // So Ditto doesn't try to filter by resource_id
        } elseif ($request->has('resource_id:in')) {
            $ids = explode(',', $request->get('resource_id:in'));
            $floatingIpIds = FloatingIp::forUser($request->user())->get()
                ->reject(function ($floatingIp) use ($ids) {
                    return !in_array($floatingIp->resource_id, $ids);
                })
                ->map(function ($floatingIp) {
                    return $floatingIp->id;
                });
            $collection = FloatingIp::whereIn('id', $floatingIpIds);
            $request->query->remove('resource_id:in');  // So Ditto doesn't try to filter by resource_id
        } else {
            $collection = FloatingIp::forUser($request->user());
        }
        // "resource_id" filtering hack - end

        $queryTransformer->config(FloatingIp::class)
            ->transform($collection);

        return FloatingIpResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $fipId)
    {
        return new FloatingIpResource(
            FloatingIp::forUser($request->user())->findOrFail($fipId)
        );
    }

    public function store(CreateRequest $request)
    {
        $floatingIp = new FloatingIp(
            $request->only(['vpc_id', 'name'])
        );

        $floatingIp->save();

        return $this->responseIdMeta($request, $floatingIp->id, 202);
    }

    public function update(UpdateRequest $request, string $fipId)
    {
        $floatingIp = FloatingIp::forUser(Auth::user())->findOrFail($fipId);
        $floatingIp->fill($request->only(['name']));

        $floatingIp->withTaskLock(function ($floatingIp) {
            $floatingIp->save();
        });

        return $this->responseIdMeta($request, $floatingIp->id, 202);
    }

    public function destroy(Request $request, string $fipId)
    {
        $floatingIp = FloatingIp::forUser($request->user())->findOrFail($fipId);

        $floatingIp->withTaskLock(function ($floatingIp) {
            $floatingIp->delete();
        });

        return response('', 202);
    }

    public function assign(AssignRequest $request, string $fipId)
    {
        $floatingIp = FloatingIp::forUser($request->user())->findOrFail($fipId);
        $resource = Resource::classFromId($request->resource_id)::findOrFail($request->resource_id);

        $floatingIp->withTaskLock(function ($floatingIp) use ($resource) {
            if (!$floatingIp->destinationNat()->exists()) {
                $nat = app()->make(Nat::class);
                $nat->destination()->associate($floatingIp);
                $nat->translated()->associate($resource);
                $nat->action = Nat::ACTION_DNAT;
                $nat->save();
            }

            if (!$floatingIp->sourceNat()->exists()) {
                $nat = app()->make(Nat::class);
                $nat->source()->associate($resource);
                $nat->translated()->associate($floatingIp);
                $nat->action = NAT::ACTION_SNAT;
                $nat->save();
            }

            $floatingIp->save();
        });

        return response('', 202);
    }

    public function unassign(Request $request, string $fipId)
    {
        $floatingIp = FloatingIp::forUser($request->user())->findOrFail($fipId);

        $floatingIp->withTaskLock(function ($floatingIp) {
            if ($floatingIp->sourceNat()->exists()) {
                $floatingIp->sourceNat->delete();
            }
            if ($floatingIp->destinationNat()->exists()) {
                $floatingIp->destinationNat->delete();
            }

            $floatingIp->save();
        });

        return response('', 202);
    }

    public function tasks(Request $request, QueryTransformer $queryTransformer, string $fipId)
    {
        $collection = FloatingIp::forUser($request->user())->findOrFail($fipId)->tasks();
        $queryTransformer->config(Task::class)
            ->transform($collection);

        return TaskResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }
}
