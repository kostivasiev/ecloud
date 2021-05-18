<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\Image\StoreRequest;
use App\Http\Requests\V2\Image\UpdateRequest;
use App\Models\V2\Image;
use App\Resources\V2\ImageMetadataResource;
use App\Resources\V2\ImageParameterResource;
use App\Resources\V2\ImageResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class ImageController
 * @package App\Http\Controllers\V2
 */
class ImageController extends BaseController
{
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = Image::forUser(Auth::user());

        $queryTransformer->config(Image::class)
            ->transform($collection);

        return ImageResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function show(string $imageId)
    {
        return new ImageResource(
            Image::forUser(Auth::user())->findOrFail($imageId)
        );
    }

    public function store(StoreRequest $request)
    {
        $model = new Image($request->only([
            'name',
            'logo_uri',
            'documentation_uri',
            'description',
            'script_template',
            'vm_template',
            'platform',
            'active',
            'public',
        ]));

        $task = $model->syncSave();

        // Sync the pivot table
        $model->availabilityZones()->sync($request->input('availability_zone_ids'));

        return $this->responseIdMeta($request, $model->id, 202, $task->id);
    }


    public function update(UpdateRequest $request, string $imageId)
    {
        $model = Image::forUser(Auth::user())->findOrFail($imageId);

        $fillable = [
            'name',
            'logo_uri',
            'documentation_uri',
            'description',
            'script_template',
            'vm_template',
            'platform',
            'active',
            'public'
        ];

        // Private images
        if (Auth::user()->isScoped() && !empty($model->reseller_id)) {
            $fillable = [
                'name',
                'logo_uri',
                'documentation_uri',
                'description',
            ];
        }

        $model->fill($request->only($fillable));

        // Sync the pivot table
        if ($request->has('availability_zone_ids') && !Auth::user()->isScoped()) {
            $model->availabilityZones()->sync($request->input('availability_zone_ids'));
        }

        $task = $model->syncSave();

        return $this->responseIdMeta($request, $model->id, 202, $task->id);
    }


    public function destroy(string $imageId)
    {
        $model = Image::forUser(Auth::user())->findOrFail($imageId);

        // Delete from pivot table
        $model->availabilityZones()->sync([]);

        $task = $model->syncDelete();
        return $this->responseTaskId($task->id);
    }

//    public function parameters(Request $request, string $imageId)
//    {
//        $collection = Image::forUser(Auth::user())->findOrFail($imageId)->parameters();
//
//        return ImageParameterResource::collection($collection->paginate(
//            $request->input('per_page', env('PAGINATION_LIMIT'))
//        ));
//    }
//
//    public function metadata(Request $request, string $imageId)
//    {
//        $collection = Image::forUser(Auth::user())->findOrFail($imageId)->metadata();
//
//        return ImageMetadataResource::collection($collection->paginate(
//            $request->input('per_page', env('PAGINATION_LIMIT'))
//        ));
//    }
}
