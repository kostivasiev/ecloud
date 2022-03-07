<?php

namespace App\Services\V1\Resource\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\JsonResource as IlluminateResource;
use App\Services\V1\Resource\Property\AbstractProperty;
use App\Services\V1\Resource\Exceptions\InvalidResourceException;

trait ResourceHelper
{
    /**
     * Set the customResource to use
     * @param $customResource string
     * @throws InvalidResourceException
     * @return void
     */
    public function setCustomResource($customResource)
    {
        if (is_null($customResource)) {
            return;
        }

        if (!class_exists($customResource) || !new $customResource($this->resource) instanceof IlluminateResource) {
            throw new InvalidResourceException("Invalid resource");
        }

        $this->customResource = $customResource;
    }

    /**
     * Transform the resource collection into an array.
     * @param Model                    $model
     * @param \Illuminate\Http\Request $request
     * @param array                    $visible
     * @return array
     */
    public function getModelAttributes(Model $model, $request, $visible = [])
    {
        if (is_null($this->customResource) === false) {
            $customResource = new $this->customResource($model);
            return $customResource->toArray($request, $visible);
        }

        $response   = [];
        $properties = $model->properties();
        $attributes = $model->attributesToArray();

        if (!empty($visible) && is_array($visible)) {
            $attributes = array_intersect_key($attributes, array_flip($visible));
        }

        foreach ($properties as $propertyKey => $property) {
            if (is_array($property) === true) {
                $response[$propertyKey] = $this->getSubProperties($model, $property, $attributes);
                continue;
            }

            if (is_null($this->customResource) && !array_key_exists($property->getDatabaseName(), $attributes)) {
                continue;
            }

            $response[$property->getFriendlyName()] = $this->getPropertyValue($model, $property, $attributes);
        }

        return $response;
    }

    /**
     * Extract sub properties for a response
     * @param Model  $model
     * @param  array $properties
     * @param  array $attributes
     * @return array
     */
    protected function getSubProperties(Model $model, array $properties, array $attributes)
    {
        $response = [];
        foreach ($properties as $propertyKey => $property) {
            if (is_array($property) === true) {
                $response[$propertyKey] = $this->getSubProperties($model, $property, $attributes);
                continue;
            }

            if (array_key_exists($property->getDatabaseName(), $attributes) === false) {
                continue;
            }

            $response[$property->getFriendlyName()] = $this->getPropertyValue($model, $property, $attributes);
        }

        return $response;
    }

    /**
     * Gets a property value from the model, including mutators
     * @param Model $model
     * @param AbstractProperty $property
     * @param array $attributes
     * @return mixed
     */
    protected function getPropertyValue(Model $model, AbstractProperty $property, array $attributes)
    {
        $property->setValue($attributes[$property->getDatabaseName()]);

        return $property->serialize();
    }

    /**
     * Get the resource's meta data
     * @param \Illuminate\Pagination\LengthAwarePaginator|
     *         \UKFast\Api\Resource\Resource|
     *         \UKFast\Api\Resource\ResourceCollection $resource
     * @return array
     */
    public function getMeta($resource)
    {
        switch (true) {
            case $resource instanceof LengthAwarePaginator:
                $total= $resource->total();
                $totalPages = ($total <= 0 ? 0 : ceil($resource->total() / $resource->perPage()));
                $meta = [
                    "pagination" => [
                        "total" => intval($resource->total()),
                        "count" => count($resource->items()),
                        "per_page" => intval($resource->perPage()),
                        "current_page" => intval($totalPages <= 0 ? 0 : $resource->currentPage()),
                        "total_pages" => $totalPages,
                        "links" => (object) [
                            'first' => $resource->url(1),
                            'previous' => $resource->previousPageUrl(),
                            'next' => $resource->nextPageUrl(),
                            'last' => $resource->url($totalPages),
                        ],
                    ],
                ];

                break;

            case $resource instanceof Collection:
                $count = intval($resource->count());
                $meta = [
                    "pagination" => [
                        "total" => $count,
                        "count" => $count,
                        "per_page" => $count,
                        "current_page" => 0,
                        "total_pages" => 0,
                        "links" => new \StdClass
                    ]
                ];
                break;

            default:
                $meta = new \stdClass;
                break;
        }
        return $meta;
    }
}
