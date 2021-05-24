<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\ImageParameter\StoreRequest;
use App\Http\Requests\V2\ImageParameter\UpdateRequest;
use App\Models\V2\Image;
use App\Models\V2\ImageParameter;
use App\Resources\V2\ImageParameterResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

class ImageParameterController extends BaseController
{
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = ImageParameter::forUser(Auth::user());

        $queryTransformer->config(ImageParameter::class)
            ->transform($collection);

        return ImageParameterResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(string $imageParameterId)
    {
        return new ImageParameterResource(
            ImageParameter::forUser(Auth::user())->findOrFail($imageParameterId)
        );
    }

    public function store(StoreRequest $request)
    {
        $image = Image::forUser(Auth::user())->findOrFail($request->input('image_id'));

        $model = new ImageParameter($request->only([
            'id',
            'name',
            'key',
            'type',
            'description',
            'required',
            'validation_rule',
        ]));
        $image->imageParameters()->save($model);

        return $this->responseIdMeta($request, $model->id, 201);
    }

    public function update(UpdateRequest $request, string $imageParameterId)
    {
        $model = ImageParameter::forUser(Auth::user())->findOrFail($imageParameterId);

        $model->fill($request->only([
            'id',
            'name',
            'key',
            'type',
            'description',
            'required',
            'validation_rule',
            'image_id'
        ]));
        $model->save();

        return $this->responseIdMeta($request, $model->id, 200);
    }

    public function destroy(string $imageParameterId)
    {
        $model = ImageParameter::forUser(Auth::user())->findOrFail($imageParameterId);
        $model->delete();
        return response('', 204);
    }
}
