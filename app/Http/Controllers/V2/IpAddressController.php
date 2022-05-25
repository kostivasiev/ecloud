<?php
namespace App\Http\Controllers\V2;

use App\Exceptions\V2\IpAddressCreationException;
use App\Http\Requests\V2\IpAddress\CreateRequest;
use App\Http\Requests\V2\IpAddress\UpdateRequest;
use App\Models\V2\IpAddress;
use App\Models\V2\Network;
use App\Resources\V2\IpAddressResource;
use App\Resources\V2\NicResource;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IpAddressController extends BaseController
{
    public function index(Request $request)
    {
        $collection = IpAddress::forUser($request->user());

        if ($request->has('sort')) {
            $collection->sortByIp();
        }

        return IpAddressResource::collection($collection->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $ipAddressId)
    {
        return new IpAddressResource(
            IpAddress::forUser($request->user())->findOrFail($ipAddressId)
        );
    }

    /**
     * @throws \App\Exceptions\V2\IpAddressCreationException
     */
    public function store(CreateRequest $request)
    {
        $model = new IpAddress(
            $request->only([
                'name',
                'network_id',
            ])
        );

        try {
            if ($request->ip_address) {
                    $model->setAddressAndSave($request->ip_address);
            } else {
                $model->allocateAddressAndSave();
            }
        } catch (LockTimeoutException) {
            throw new IpAddressCreationException;
        }

        $task = $model->syncSave();

        return $this->responseIdMeta($request, $model->id, 202, $task->id);
    }

    public function update(UpdateRequest $request, string $ipAddressId)
    {
        $model = IpAddress::forUser(Auth::user())->findOrFail($ipAddressId);
        $model->fill($request->only(['name']));

        $task = $model->syncSave();

        return $this->responseIdMeta($request, $model->id, 202, $task->id);
    }

    public function destroy(Request $request, string $ipAddressId)
    {
        $model = IpAddress::forUser($request->user())->findOrFail($ipAddressId);

        $task = $model->syncDelete();
        return $this->responseTaskId($task->id, 204);
    }

    public function nics(Request $request, string $ipAddressId)
    {
        $collection = IpAddress::forUser($request->user())->findOrFail($ipAddressId)->nics();

        return NicResource::collection($collection->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }
}
