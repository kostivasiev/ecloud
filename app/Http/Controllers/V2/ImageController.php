<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\Image\StoreRequest;
use App\Http\Requests\V2\Image\UpdateRequest;
use App\Models\V2\Image;
use App\Resources\V2\ImageMetadataResource;
use App\Resources\V2\ImageParameterResource;
use App\Resources\V2\ImageResource;
use App\Resources\V2\SoftwareResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
        try {
            $model = new Image($request->only([
                'name',
                'logo_uri',
                'documentation_uri',
                'description',
                'script_template',
                'readiness_script',
                'vm_template',
                'platform',
                'active',
                'public',
                'visibility',
            ]));

            $task = $model->syncSave();
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }

        // Sync the pivot tables
        $model->availabilityZones()->sync($request->input('availability_zone_ids'));

        $model->software()->sync($request->input('software_ids'));

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
            'readiness_script',
            'vm_template',
            'platform',
            'active',
            'public',
            'visibility',
        ];

        // Private images
        if (Auth::user()->isScoped() && !empty($model->vpc_id)) {
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

        if ($request->has('software_ids') && !Auth::user()->isScoped()) {
            $model->software()->sync($request->input('software_ids'));
        }

        $task = $model->syncSave();

        return $this->responseIdMeta($request, $model->id, 202, $task->id);
    }

    public function destroy(string $imageId)
    {
        $model = Image::forUser(Auth::user())->findOrFail($imageId);

        $task = $model->syncDelete();
        return $this->responseTaskId($task->id);
    }

    public function parameters(Request $request, string $imageId)
    {
        $collection = Image::forUser(Auth::user())->findOrFail($imageId)->imageParameters()->forUser(Auth::user());

        return ImageParameterResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function metadata(Request $request, string $imageId)
    {
        $collection = Image::forUser(Auth::user())->findOrFail($imageId)->imageMetadata();

        return ImageMetadataResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    public function software(Request $request, string $imageId)
    {
        $collection = Image::forUser(Auth::user())->findOrFail($imageId)->software();

        return SoftwareResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }
}
