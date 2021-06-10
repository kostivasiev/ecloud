<?php
namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\LocalEndpoint\Create;
use App\Http\Requests\V2\LocalEndpoint\Update;
use App\Models\V2\FloatingIp;
use App\Models\V2\LocalEndpoint;
use App\Models\V2\Vpn;
use App\Resources\V2\LocalEndpointResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

class LocalEndpointController extends BaseController
{
    public function index(Request $request)
    {
        $collection = LocalEndpoint::forUser($request->user());
        (new QueryTransformer($request))
            ->config(LocalEndpoint::class)
            ->transform($collection);

        return LocalEndpointResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $localEndpointId)
    {
        return new LocalEndpointResource(
            LocalEndpoint::forUser($request->user())->findOrFail($localEndpointId)
        );
    }

    public function store(Create $request)
    {
        $localEndpoint = new LocalEndpoint(
            $request->only(['name', 'vpn_id', 'fip_id'])
        );
        // if no fip_id supplied then create one
        if (!$request->has('fip_id')) {
            $vpn = Vpn::forUser($request->user())->findOrFail($request->get('vpn_id'));
            $floatingIp = app()->make(FloatingIp::class, [
                'attributes' => [
                    'vpc_id' => $vpn->router->vpc_id,
                ]
            ]);
            $floatingIp->save();
            $localEndpoint->fip_id = $floatingIp->id;
        }
        $localEndpoint->save();
        return $this->responseIdMeta($request, $localEndpoint->id, 202);
    }

    public function update(Update $request, string $localEndpointId)
    {
        $localEndpoint = LocalEndpoint::forUser(Auth::user())->findOrFail($localEndpointId);
        $localEndpoint->fill($request->only(['name', 'vpn_id', 'fip_id']));
        $localEndpoint->save();
        return $this->responseIdMeta($request, $localEndpoint->id, 202);
    }

    public function destroy(Request $request, string $localEndpointId)
    {
        LocalEndpoint::forUser($request->user())->findOrFail($localEndpointId)->delete();
        return response('', 204);
    }
}