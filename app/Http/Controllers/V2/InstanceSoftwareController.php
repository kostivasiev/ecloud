<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\VpnProfile\CreateRequest;
use App\Http\Requests\V2\VpnProfile\Update;
use App\Models\V2\VpnProfile;
use App\Resources\V2\VpnProfileResource;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\QueryTransformer;

class InstanceSoftwareController extends BaseController
{
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = VpnProfile::query();

        $queryTransformer->config(VpnProfile::class)
            ->transform($collection);

        return VpnProfileResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $vpnProfileId)
    {
        return new VpnProfileResource(
            VpnProfile::findOrFail($vpnProfileId)
        );
    }

    public function create(CreateRequest $request)
    {
        $vpnProfile = new VpnProfile($request->only([
            'name',
            'ike_version',
            'encryption_algorithm',
            'digest_algorithm',
            'diffie_hellman',
        ]));
        $vpnProfile->save();
        $vpnProfile->refresh();
        return $this->responseIdMeta($request, $vpnProfile->id, 201);
    }

    public function update(Update $request, string $vpnProfileId)
    {
        $vpnProfile = VpnProfile::findOrFail($vpnProfileId);
        $vpnProfile->fill($request->only([
            'name',
            'ike_version',
            'encryption_algorithm',
            'digest_algorithm',
            'diffie_hellman',
        ]));
        $vpnProfile->save();
        return $this->responseIdMeta($request, $vpnProfile->id, 200);
    }

    public function destroy(Request $request, string $vpnProfileId)
    {
        VpnProfile::findOrFail($vpnProfileId)->delete();
        return response('', 204);
    }
}
