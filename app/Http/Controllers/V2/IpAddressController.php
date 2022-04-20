<?php
namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\IpAddress\CreateRequest;
use App\Http\Requests\V2\IpAddress\UpdateRequest;
use App\Models\V2\IpAddress;
use App\Resources\V2\IpAddressResource;
use App\Resources\V2\NicResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IpAddressController extends BaseController
{
    public function index(Request $request)
    {
        $collection = IpAddress::forUser($request->user());

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

    public function store(CreateRequest $request)
    {
        $ipAddress = new IpAddress(
            $request->only([
                'name',
                'ip_address',
                'network_id',
            ])
        );

        $ipAddress->save();
        return $this->responseIdMeta($request, $ipAddress->id, 201);
    }

    public function update(UpdateRequest $request, string $ipAddressId)
    {
        $ipAddress = IpAddress::forUser(Auth::user())->findOrFail($ipAddressId);
        $ipAddress->fill($request->only(['name']));
        $ipAddress->save();

        return $this->responseIdMeta($request, $ipAddress->id, 200);
    }

    public function destroy(Request $request, string $ipAddressId)
    {
        $ipAddress = IpAddress::forUser($request->user())->findOrFail($ipAddressId);
        $ipAddress->delete();
        return response('', 204);
    }

    public function nics(Request $request, string $ipAddressId)
    {
        $collection = IpAddress::forUser($request->user())->findOrFail($ipAddressId)->nics();

        return NicResource::collection($collection->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }
}
