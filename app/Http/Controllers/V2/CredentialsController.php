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
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $collection = Credential::filterHidden($request);

        return CredentialResource::collection(
            $collection->search()
                ->paginate(
                    $request->input('per_page', env('PAGINATION_LIMIT')),
                )
        );
    }

    /**
     * @param Request $request
     * @param string $credentialsId
     * @return CredentialResource
     */
    public function show(Request $request, string $credentialsId)
    {
        return new CredentialResource(
            Credential::filterHidden($request)->findOrFail($credentialsId)
        );
    }

    /**
     * @param CreateCredentialRequest $request
     * @return \Illuminate\Http\JsonResponse|Response
     */
    public function store(CreateCredentialRequest $request)
    {
        $credential = new Credential($request->only(['name', 'resource_id', 'host', 'username', 'password', 'port']));
        $credential->is_hidden = false;
        if ($this->isAdmin) {
            $credential->is_hidden = $request->get('is_hidden', false);
        }
        $credential->save();
        return $this->responseIdMeta($request, $credential->id, 201);
    }

    /**
     * @param UpdateCredentialRequest $request
     * @param string $credentialsId
     * @return \Illuminate\Http\JsonResponse|Response
     */
    public function update(UpdateCredentialRequest $request, string $credentialsId)
    {
        $credential = Credential::findOrFail($credentialsId);
        $credential->fill($request->only(['name', 'resource_id', 'host', 'username', 'password', 'port']));
        if ($this->isAdmin) {
            $credential->is_hidden = $request->get('is_hidden', $credential->is_hidden);
        }
        $credential->save();
        return $this->responseIdMeta($request, $credential->id, 200);
    }

    public function destroy(Request $request, string $credentialsId)
    {
        Credential::findOrFail($credentialsId)
            ->delete();
        return response('', 204);
    }
}
