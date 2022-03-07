<?php

namespace App\Services\V1\Resource;

use Illuminate\Http\Resources\Json\ResourceCollection as IlluminateResourceCollection;
use App\Services\V1\Resource\Traits\ResourceHelper;
use App\Services\V1\Resource\Resource as FastResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class ResourceCollection extends IlluminateResourceCollection
{
    use ResourceHelper;

    /**
     * A custom resource to use for overriding the automatic responses
     * @var
     */
    protected $customResource;

    /**
     * Transform model to array
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request, $visible = [])
    {
        $response = [
            "data" => []
        ];

        if ($this->resource instanceof LengthAwarePaginator) {
            $collection = $this->resource->items();
        } else {
            $collection = $this->resource->toArray();
        }
        foreach ($collection as $itemKey => $item) {
            if ($item->resource instanceof Model) {
                $response["data"][] = $this->getModelAttributes($item->resource, $request, $visible);
                continue;
            }

            if (empty($this->customResource) === false) {
                $customResource = new $this->customResource($item->resource);
                $response["data"][] = $customResource->toArray($request, $visible);

                continue;
            }

            $response["data"][] = $item->resource;
        }

        $response["meta"] = $this->getMeta($this->resource);

        return $response;
    }

    /**
     * Transform array to model properties
     * @param array $inputData
     * @param string $modelName
     * @param null|string $friendlyPrimaryKey
     * @return \App\Services\V1\Resource\ResourceCollection
     */
    public function fromArray($inputData, $modelName, $friendlyPrimaryKey = null)
    {
        foreach ($inputData as $inputKey => $input) {
            $resource = new FastResource(new $modelName);
            $this->collection->push($resource->fromArray($input, $modelName, $friendlyPrimaryKey));
        }

        return $this;
    }
}
