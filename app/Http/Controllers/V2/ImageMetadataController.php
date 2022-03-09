<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\ImageMetadata\StoreRequest;
use App\Http\Requests\V2\ImageMetadata\UpdateRequest;
use App\Models\V2\ImageMetadata;
use App\Resources\V2\ImageMetadataResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImageMetadataController extends BaseController
{
    public function index(Request $request)
    {
        $collection = ImageMetadata::forUser(Auth::user());

        return ImageMetadataResource::collection(
            $collection->search()
                ->paginate(
                    $request->input('per_page', env('PAGINATION_LIMIT'))
                )
        );
    }

    public function show(string $imageMetadataId)
    {
        return new ImageMetadataResource(
            ImageMetadata::forUser(Auth::user())->findOrFail($imageMetadataId)
        );
    }

    public function store(StoreRequest $request)
    {
        $model = new ImageMetadata($request->only([
            'image_id',
            'key',
            'value'
        ]));

        $model->save();

        return $this->responseIdMeta($request, $model->id, 201);
    }

    public function update(UpdateRequest $request, string $imageMetadataId)
    {
        $model = ImageMetadata::forUser(Auth::user())->findOrFail($imageMetadataId);

        $model->fill($request->only([
            'image_id',
            'key',
            'value'
        ]));

        $model->save();

        return $this->responseIdMeta($request, $model->id, 200);
    }

    public function destroy(string $imageMetadataId)
    {
        $model = ImageMetadata::forUser(Auth::user())->findOrFail($imageMetadataId);
        $model->delete();
        return response('', 204);
    }
}
