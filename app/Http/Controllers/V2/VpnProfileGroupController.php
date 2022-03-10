<?php
namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\VpnProfileGroup\Create;
use App\Http\Requests\V2\VpnProfileGroup\Update;
use App\Models\V2\VpnProfileGroup;
use App\Resources\V2\VpnProfileGroupResource;
use Illuminate\Http\Request;

class VpnProfileGroupController extends BaseController
{
    public function index(Request $request)
    {
        $collection = VpnProfileGroup::forUser($request->user());

        return VpnProfileGroupResource::collection($collection->search()->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $vpnProfileGroupId)
    {
        return new VpnProfileGroupResource(
            VpnProfileGroup::forUser($request->user())->findOrFail($vpnProfileGroupId)
        );
    }

    public function create(Create $request)
    {
        $vpns = new VpnProfileGroup($request->only([
            'name',
            'description',
            'availability_zone_id',
            'ike_profile_id',
            'ipsec_profile_id',
        ]));
        $vpns->save();
        return $this->responseIdMeta($request, $vpns->id, 201);
    }

    public function update(Update $request, string $vpnProfileGroupId)
    {
        $vpns = VpnProfileGroup::findOrFail($vpnProfileGroupId);
        $vpns->fill($request->only([
            'name',
            'description',
            'availability_zone_id',
            'ike_profile_id',
            'ipsec_profile_id',
        ]));
        $vpns->save();
        return $this->responseIdMeta($request, $vpns->id, 200);
    }

    public function destroy(Request $request, string $vpnProfileGroupId)
    {
        VpnProfileGroup::findOrFail($vpnProfileGroupId)->delete();
        return response('', 204);
    }
}
