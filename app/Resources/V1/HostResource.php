<?php

namespace App\Resources\V1;

use App\Services\V1\Resource\CustomResource;

class HostResource extends CustomResource
{
    public function toArray($request, $visible = [])
    {
        $attributes = $this->getModelAttributes($this->resource, $request, $visible);

        if (isset($this->resource->usage)) {
            $attributes = array_merge_recursive($attributes, [
                'ram' => [
                    'reserved' => $this->resource->usage->ram->reserved,
                    'allocated' => $this->resource->usage->ram->allocated,
                    'available' => $this->resource->usage->ram->available,
                ],
            ]);
        }

        if (!$request->user()->isAdmin()) {
            unset($attributes['internal_name']);
        }

        return $this->filterProperties($request, $attributes);
    }
}
