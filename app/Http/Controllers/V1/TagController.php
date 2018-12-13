<?php

namespace App\Http\Controllers\V1;

use UKFast\DB\Ditto\QueryTransformer;

use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\Api\Resource\Traits\RequestHelper;

use Illuminate\Http\Request;

use App\Models\V1\Tag;
use App\Exceptions\V1\TagNotFoundException;

class TagController extends BaseController
{
    use ResponseHelper, RequestHelper;

    /**
     * @param Request $request
     * @param $solutionId
     * @return \Illuminate\Http\Response
     * @throws \App\Exceptions\V1\SolutionNotFoundException
     */
    public function indexSolutionTags(Request $request, $solutionId)
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

    public function showSolutionTag(Request $request, $solutionId, $tagKey)
    {
        SolutionController::getSolutionById($request, $solutionId);

        $tag = Tag::withReseller($request->user->resellerId)
            ->withSolution($solutionId)
            ->withKey($tagKey)
            ->first();

        if (is_null($tag)) {
            throw new TagNotFoundException('Tag with key \'' . $tagKey . '\' not found');
        }

        return $this->respondItem(
            $request,
            $tag
        );
    }

    public function updateSolutionTag(Request $request, $solutionId, $tagKey)
    {
        SolutionController::getSolutionById($request, $solutionId);

        $tag = Tag::withReseller($request->user->resellerId)
            ->withSolution($solutionId)
            ->withKey($tagKey)
            ->first();

        if (is_null($tag)) {
            throw new TagNotFoundException('Tag with key \'' . $tagKey . '\' not found');
        }

        $this->validate($request, [
            'value' => 'regex:/'.Tag::KEY_FORMAT_REGEX.'/',
        ]);

        $tag->metadata_value = $request->input('value');
        if (!$tag->save()) {
            // todo log and error
        }

        return $this->respondSave(
            $request,
            $tag
        );
    }

    /**
     * @param Request $request
     * @param $solutionId
     * @param $tagKey
     * @return \Illuminate\Http\Response
     * @throws TagNotFoundException
     * @throws \App\Exceptions\V1\SolutionNotFoundException
     */
    public function destroySolutionTag(Request $request, $solutionId, $tagKey)
    {
        SolutionController::getSolutionById($request, $solutionId);

        $tag = Tag::withReseller($request->user->resellerId)
            ->withSolution($solutionId)
            ->withKey($tagKey)
            ->first();

        if (is_null($tag)) {
            throw new TagNotFoundException('Tag with key \'' . $tagKey . '\' not found');
        }

        if (!$tag->delete()) {
            // todo log and error
        }

        return $this->respondEmpty();
    }
}
