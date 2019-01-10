<?php

namespace App\Http\Controllers\V1;

use UKFast\DB\Ditto\QueryTransformer;

use UKFast\Api\Resource\Traits\ResponseHelper;
use UKFast\Api\Resource\Traits\RequestHelper;

use Illuminate\Http\Request;

use App\Models\V1\Tag;
use App\Exceptions\V1\TagNotFoundException;
use UKFast\Api\Exceptions\BadRequestException;

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

    public function createSolutionTag(Request $request, $solutionId)
    {
        $solution = SolutionController::getSolutionById($request, $solutionId);

        $this->validate($request, [
            'key' => ['required', 'regex:/'.Tag::KEY_FORMAT_REGEX.'/'],
            'value' => ['required'],
        ]);

        $existingTags = Tag::withReseller($request->user->resellerId)
            ->withSolution($solutionId)
            ->withKey($request->input('key'));

        if ($existingTags->count() > 0) {
            throw new BadRequestException('Tag with key \'' . $request->input('key') . '\' already exists');
        }

        $tag = new Tag;
        $tag->metadata_reseller_id = $solution->ucs_reseller_reseller_id;
        $tag->metadata_key = $request->input('key');
        $tag->metadata_value = $request->input('value');
        $tag->metadata_resource = 'ucs_reseller';
        $tag->metadata_resource_id = $solution->getKey();
        $tag->metadata_created = date('Y-m-d H:i:s');
        $tag->metadata_createdby = 'API Client';
        $tag->metadata_createdby_id = $request->user->applicationId;

        if (!$tag->save()) {
            // todo log and error
        }

        return $this->respondSave(
            $request,
            $tag,
            201,
            null,
            [],
            [],
            $request->path() . '/' . $tag->metadata_key
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
            'value' => ['required'],
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

    public function indexVMTags(Request $request, $vmId)
    {
        VirtualMachineController::getVirtualMachineById($request, $vmId);

        $collection = Tag::withReseller($request->user->resellerId)
            ->withServer($vmId);

        (new QueryTransformer($request))
            ->config(Tag::class)
            ->transform($collection);

        return $this->respondCollection(
            $request,
            $collection->paginate($this->perPage)
        );
    }

    public function showVMTag(Request $request, $vmId, $tagKey)
    {
        VirtualMachineController::getVirtualMachineById($request, $vmId);

        $tag = Tag::withReseller($request->user->resellerId)
            ->withServer($vmId)
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

    public function createVMTag(Request $request, $vmId)
    {
        $server = VirtualMachineController::getVirtualMachineById($request, $vmId);

        $this->validate($request, [
            'key' => ['required', 'regex:/'.Tag::KEY_FORMAT_REGEX.'/'],
            'value' => ['required'],
        ]);

        $existingTags = Tag::withReseller($request->user->resellerId)
            ->withServer($vmId)
            ->withKey($request->input('key'));

        if ($existingTags->count() > 0) {
            throw new BadRequestException('Tag with key \'' . $request->input('key') . '\' already exists');
        }

        $tag = new Tag;
        $tag->metadata_reseller_id = $server->servers_reseller_id;
        $tag->metadata_key = $request->input('key');
        $tag->metadata_value = $request->input('value');
        $tag->metadata_resource = 'server';
        $tag->metadata_resource_id = $server->getKey();
        $tag->metadata_created = date('Y-m-d H:i:s');
        $tag->metadata_createdby = 'API Client';
        $tag->metadata_createdby_id = $request->user->applicationId;

        if (!$tag->save()) {
            // todo log and error
        }

        return $this->respondSave(
            $request,
            $tag,
            201,
            null,
            [],
            [],
            $request->path() . '/' . $tag->metadata_key
        );
    }

    public function updateVMTag(Request $request, $vmId, $tagKey)
    {
        VirtualMachineController::getVirtualMachineById($request, $vmId);

        $tag = Tag::withReseller($request->user->resellerId)
            ->withServer($vmId)
            ->withKey($tagKey)
            ->first();

        if (is_null($tag)) {
            throw new TagNotFoundException('Tag with key \'' . $tagKey . '\' not found');
        }

        $this->validate($request, [
            'value' => ['required'],
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

    public function destroyVMTag(Request $request, $vmId, $tagKey)
    {
        VirtualMachineController::getVirtualMachineById($request, $vmId);

        $tag = Tag::withReseller($request->user->resellerId)
            ->withServer($vmId)
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
