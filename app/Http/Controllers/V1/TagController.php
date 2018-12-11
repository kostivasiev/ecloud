<?php

namespace App\Http\Controllers\V1;

use UKFast\DB\Ditto\QueryTransformer;

use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\Api\Resource\Traits\RequestHelper;

use Illuminate\Http\Request;

use App\Models\V1\Tag;
use App\Exceptions\V1\TagNotFoundException;

use Illuminate\Database\Eloquent\ModelNotFoundException;

class TagController extends BaseController
{
    use ResponseHelper, RequestHelper;

    /**
     * @param Request $request
     * @param $solutionId
     * @return \Illuminate\Http\Response
     * @throws \App\Exceptions\V1\SolutionNotFoundException
     */
    public function showSolutionTags(Request $request, $solutionId)
    {
        SolutionController::getSolutionById($request, $solutionId);

        $collection = Tag::withReseller($request->user->resellerId)
            ->withSolution($solutionId);

        (new QueryTransformer($request))
            ->config(Tag::class)
            ->transform($collection);

        return $this->respondCollection(
            $request,
            $collection->paginate($this->perPage)
        );
    }
}
