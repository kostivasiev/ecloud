<?php

namespace App\Services\V1\Resource\Traits;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use App\Services\V1\Resource\Resource as FastResource;
use App\Services\V1\Resource\ResourceCollection as FastResourceCollection;
use App\Services\V1\Resource\Exceptions\InvalidReceiptException;

trait RequestHelper
{
    /**
     * Respond with a collection
     * @param $request
     * @param $modelName
     * @param null $customResource
     * @param null $friendlyPrimaryKey
     * @return FastResourceCollection
     */
    public function receiveCollection($request, $modelName, $customResource = null, $friendlyPrimaryKey = null)
    {
        $resource = new FastResourceCollection(new Collection);
        return $this->formatRequestItem($request, $resource, $modelName, $customResource, $friendlyPrimaryKey);
    }

    /**
     * Respond with an item
     * @param Request $request
     * @param string $modelName
     * @param null $customResource
     * @param null $friendlyPrimaryKey
     * @return FastResource
     */
    public function receiveItem(Request $request, $modelName, $customResource = null, $friendlyPrimaryKey = null)
    {
        $resource = new FastResource(new $modelName);
        return $this->formatRequestItem($request, $resource, $modelName, $customResource, $friendlyPrimaryKey);
    }

    /**
     * Format the response data
     * @param Request $request
     * @param $resource FastResourceCollection | FastResource
     * @param $modelName
     * @param null $customResource
     * @param null $friendlyPrimaryKey
     * @return FastResource | FastResourceCollection
     *@throws InvalidReceiptException
     */
    private function formatRequestItem(
        Request $request,
        $resource,
        $modelName,
        $customResource = null,
        $friendlyPrimaryKey = null
    ) {
        if (!$resource instanceof FastResource
            && !$resource instanceof FastResourceCollection
        ) {
            throw new InvalidReceiptException("Invalid resource");
        }

        if (!is_null($customResource)) {
            $resource->setCustomResource($customResource);
        }

        if ($request->method() === 'POST') {
            $request->offsetUnset('id');
            $request->offsetUnset((string) $friendlyPrimaryKey);
        }

        $data = $resource->fromArray($request->toArray(), $modelName, $friendlyPrimaryKey);

        return $data;
    }
}
