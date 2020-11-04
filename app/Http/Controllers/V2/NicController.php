<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\CreateNicRequest;
use App\Http\Requests\V2\UpdateNicRequest;
use App\Models\V2\Nic;
use App\Resources\V2\NicResource;
use App\Rules\V2\IpAvailable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;
use UKFast\DB\Ditto\QueryTransformer;

class NicController extends BaseController
{
    /**
     * @param Request $request
     * @param QueryTransformer $queryTransformer
     * @return AnonymousResourceCollection
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = Nic::forUser($request->user);
        $queryTransformer->config(Nic::class)
            ->transform($collection);

        return NicResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param Request $request
     * @param string $nicId
     * @return \App\Http\Resources\NicResource
     */
    public function show(Request $request, string $nicId)
    {
        return new NicResource(
            Nic::forUser($request->user)->findOrFail($nicId)
        );
    }

    /**
     * @param CreateNicRequest $request
     * @return JsonResponse
     */
    public function create(CreateNicRequest $request)
    {
        $nic = new Nic($request->only([
            'mac_address',
            'instance_id',
            'network_id',
            'ip_address',
        ]));
        $nic->save();
        return $this->responseIdMeta($request, $nic->getKey(), 201);
    }

    /**
     * @param UpdateNicRequest $request
     * @param string $nicId
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(UpdateNicRequest $request, string $nicId)
    {
        $nic = Nic::forUser(app('request')->user)->findOrFail($nicId);
        $nic->fill($request->only([
            'mac_address',
            'instance_id',
            'network_id',
            'ip_address'
        ]));
        $this->validate($request, ['ip_address' => [new IpAvailable($nic->network_id)]]);
        $nic->save();
        return $this->responseIdMeta($request, $nic->getKey(), 200);
    }

    /**
     * @param Request $request
     * @param string $nicId
     * @return JsonResponse
     */
    public function destroy(Request $request, string $nicId)
    {
        $nic = Nic::forUser($request->user)->findOrFail($nicId);
        $nic->delete();
        return response()->json([], 204);
    }
}
