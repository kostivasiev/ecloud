<?php

namespace App\Resources\V1;

use UKFast\Api\Resource\CustomResource;
use UKFast\Api\Resource\ResourceCollection as FastResourceCollection;
use App\Models\V1\Datastore;

class DatastoreResource extends CustomResource
{
    /**
     * Transform the data resource into an array.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param \Illuminate\Http\Request $request
     * @param array $visible
     * @return array
     */
    public function toArray($request, $visible = [])
    {
        $response_type = null;
        if ($visible == Datastore::$collectionProperties) {
            $response_type = 'collection';
        } elseif ($visible == Datastore::$itemProperties) {
            $response_type = 'item';
        }

        // return additional admin properties if required
        // Auth::user()->isAdmin()
        // $request->user()
        if ($request->user->isAdmin) {
            $visible = array_merge(
                $visible,
                Datastore::$adminProperties
            );
        }

        $attributes = $this->getModelAttributes($this->resource, $request, $visible);

        if ($response_type === 'item') {
            $attributes = array_merge($attributes, [
                'allocated' => $this->resource->usage->provisioned,
                'available' => $this->resource->usage->available,
            ]);
        }

        return $this->filterProperties($request, $attributes);
    }
}
