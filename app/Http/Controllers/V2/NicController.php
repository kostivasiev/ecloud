<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\CreateNicRequest;
use App\Http\Requests\V2\UpdateNicRequest;
use App\Models\V2\Nic;
use App\Resources\V2\NicResource;
use App\Rules\V2\IpAvailable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

class NicController extends BaseController
{
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = Nic::forUser($request->user());
        $queryTransformer->config(Nic::class)
            ->transform($collection);

        return NicResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $nicId)
    {
        return new NicResource(
            Nic::forUser($request->user())->findOrFail($nicId)
        );
    }

    public function create(CreateNicRequest $request)
    {
        $nic = new Nic($request->only([
            'mac_address',
            'instance_id',
            'network_id',
            'ip_address',
        ]));

        $nic->save();

        return $this->responseIdMeta($request, $nic->id, 202);
    }

    public function update(UpdateNicRequest $request, string $nicId)
    {
        $nic = Nic::forUser(Auth::user())->findOrFail($nicId);
        $nic->fill($request->only([
            'mac_address',
            'instance_id',
            'network_id',
            'ip_address'
        ]));
        $this->validate($request, ['ip_address' => [new IpAvailable($nic->network_id)]]);

        $nic->withTaskLock(function ($nic) {
            $nic->save();
        });

        return $this->responseIdMeta($request, $nic->id, 202);
    }

    public function destroy(Request $request, string $nicId)
    {
        $nic = Nic::forUser($request->user())->findOrFail($nicId);

        $nic->withTaskLock(function ($nic) {
            $nic->delete();
        });

        return response('', 202);
    }
}
