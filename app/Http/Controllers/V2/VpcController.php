<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\Vpc\CreateRequest;
use App\Http\Requests\V2\Vpc\DeployDefaultsRequest;
use App\Http\Requests\V2\Vpc\UpdateRequest;
use App\Jobs\Vpc\Defaults\ConfigureVpcDefaults;
use App\Jobs\Vpc\UpdateSupportEnabledBilling;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use App\Resources\V2\InstanceResource;
use App\Resources\V2\LoadBalancerResource;
use App\Resources\V2\TaskResource;
use App\Resources\V2\VolumeResource;
use App\Resources\V2\VpcResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

class VpcController extends BaseController
{
    public function index(Request $request)
    {
        $collection = Vpc::forUser($request->user());

        return VpcResource::collection($collection->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $vpcId)
    {
        return new VpcResource(
            Vpc::forUser($request->user())->findOrFail($vpcId)
        );
    }

    public function create(CreateRequest $request)
    {
        $vpc = app()->make(Vpc::class);
        $vpc->fill($request->only(['name', 'region_id', 'advanced_networking']));
        if ($request->has('console_enabled')) {
            if (!$this->isAdmin) {
                return response()->json([
                    'errors' => [
                        'title' => 'Forbidden',
                        'details' => 'Console access cannot be modified',
                        'status' => Response::HTTP_FORBIDDEN,
                    ]
                ], Response::HTTP_FORBIDDEN);
            }
            $vpc->console_enabled = $request->input('console_enabled', true);
        }

        $vpc->reseller_id = $this->resellerId;

        if ($request->has('support_enabled') && $request->input('support_enabled') === true) {
            dispatch(new UpdateSupportEnabledBilling($vpc, true));
        }

        $task = $vpc->syncSave();

        return $this->responseIdMeta($request, $vpc->id, 202, $task->id);
    }

    public function update(UpdateRequest $request, string $vpcId)
    {
        $vpc = Vpc::forUser(Auth::user())->findOrFail($vpcId);
        $vpc->name = $request->input('name', $vpc->name);

        if ($request->has('console_enabled')) {
            if (!$this->isAdmin) {
                return response()->json([
                    'errors' => [
                        'title' => 'Forbidden',
                        'details' => 'Console access cannot be modified',
                        'status' => Response::HTTP_FORBIDDEN,
                    ]
                ], Response::HTTP_FORBIDDEN);
            }
            $vpc->console_enabled = $request->input('console_enabled', $vpc->console_enabled);
        }
        if ($this->isAdmin) {
            $vpc->reseller_id = $request->input('reseller_id', $vpc->reseller_id);
        }

        if ($request->has('support_enabled')) {
            if ($request->input('support_enabled') == true && !$vpc->support_enabled) {
                dispatch(new UpdateSupportEnabledBilling($vpc, true));
            }

            if ($request->input('support_enabled') == false && $vpc->support_enabled) {
                dispatch(new UpdateSupportEnabledBilling($vpc, false));
            }
        }

        $task = $vpc->syncSave();
        return $this->responseIdMeta($request, $vpc->id, 202, $task->id);
    }

    public function destroy(Request $request, string $vpcId)
    {
        $task = Vpc::forUser($request->user())->findOrFail($vpcId)->syncDelete();
        return $this->responseTaskId($task->id);
    }

    public function volumes(Request $request, string $vpcId)
    {
        $collection = Vpc::forUser($request->user())->findOrFail($vpcId)->volumes()->forUser($request->user());

        return VolumeResource::collection($collection->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function instances(Request $request, string $vpcId)
    {
        $collection = Vpc::forUser($request->user())->findOrFail($vpcId)->instances();

        return InstanceResource::collection($collection->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function tasks(Request $request, string $vpcId)
    {
        $collection = Vpc::forUser($request->user())->findOrFail($vpcId)->tasks();

        return TaskResource::collection($collection->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function loadBalancers(Request $request, string $vpcId)
    {
        $collection = Vpc::forUser($request->user())->findOrFail($vpcId)->loadBalancers();

        return LoadBalancerResource::collection(
            $collection->search()
                ->paginate(
                    $request->input('per_page', env('PAGINATION_LIMIT'))
                )
        );
    }

    public function deployDefaults(DeployDefaultsRequest $request, string $vpcId)
    {
        $vpc = Vpc::forUser($request->user())->findOrFail($vpcId);
        $availabilityZone = AvailabilityZone::forUser($request->user())
            ->findOrFail($request->get('availability_zone_id'));

        // Configure VPC defaults (Rincewind)
        $this->dispatch(new ConfigureVpcDefaults($vpc, $availabilityZone));

        return response('', 202);
    }
}
