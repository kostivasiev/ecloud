<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\SshKeyPair\CreateRequest;
use App\Http\Requests\V2\SshKeyPair\UpdateRequest;
use App\Models\V2\SshKeyPair;
use App\Resources\V2\SshKeyPairResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

class SshKeyPairController extends BaseController
{
    public function index(Request $request)
    {
        $collection = SshKeyPair::forUser($request->user());
        (new QueryTransformer($request))
            ->config(SshKeyPair::class)
            ->transform($collection);

        return SshKeyPairResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(Request $request, string $keypairId)
    {
        return new SshKeyPairResource(
            SshKeyPair::forUser($request->user())->findOrFail($keypairId)
        );
    }

    public function create(CreateRequest $request)
    {
        $keypair = app()->make(SshKeyPair::class);
        $keypair->fill($request->only(['name', 'public_key']));
        $keypair->reseller_id = $this->resellerId;

        $keypair->save();

        return $this->responseIdMeta($request, $keypair->id, 200);
    }

    public function update(UpdateRequest $request, string $keypairId)
    {
        $keypair = SshKeyPair::forUser(Auth::user())->findOrFail($keypairId);
        $keypair->fill($request->only(['name', 'public_key']));
        $keypair->save();

        return $this->responseIdMeta($request, $keypair->id, 200);
    }

    public function destroy(Request $request, string $keypairId)
    {
        $keypair = SshKeyPair::forUser($request->user())->findOrFail($keypairId);
        $keypair->delete();

        return response('', 200);
    }
}
