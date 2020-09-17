<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\CreateCredentialRequest;
use App\Http\Requests\V2\UpdateCredentialRequest;
use App\Models\V2\Credential;
use App\Resources\V2\CredentialResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class CredentialsController
 * @package App\Http\Controllers\V2
 */
class CredentialsController extends BaseController
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $collection = Credential::query();

        (new QueryTransformer($request))
            ->config(Credential::class)
            ->transform($collection);

        return CredentialResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $credentialsId
     * @return CredentialResource
     */
    public function show(Request $request, string $credentialsId)
    {
        return new CredentialResource(
            Credential::findOrFail($credentialsId)
        );
    }

    /**
     * @param CreateCredentialRequest $request
     * @return \Illuminate\Http\JsonResponse|Response
     */
    public function store(CreateCredentialRequest $request)
    {
        $credential = new Credential($request->only(['name', 'resource_id', 'host', 'user', 'password', 'port']));
        $credential->save();
        $credential->refresh();
        return $this->responseIdMeta($request, $credential->getKey(), 201);
    }

    /**
     * @param UpdateCredentialRequest $request
     * @param string $credentialsId
     * @return \Illuminate\Http\JsonResponse|Response
     */
    public function update(UpdateCredentialRequest $request, string $credentialsId)
    {
        $credential = Credential::findOrFail($credentialsId);
        $credential->fill($request->only(['name', 'resource_id', 'host', 'user', 'password', 'port']));
        $credential->save();
        return $this->responseIdMeta($request, $credential->getKey(), 200);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string $credentialsId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $credentialsId)
    {
        Credential::findOrFail($credentialsId)->delete();
        return response()->json([], 204);
    }
}
