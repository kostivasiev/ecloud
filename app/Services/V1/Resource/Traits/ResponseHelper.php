<?php

namespace App\Services\V1\Resource\Traits;

use App\Services\V1\Resource\Exceptions\InvalidResourceException;
use App\Services\V1\Resource\Exceptions\InvalidRouteException;
use Illuminate\Http\Resources\Json\JsonResource as Resource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Response;
use App\Services\V1\Resource\Resource as FastResource;
use App\Services\V1\Resource\ResourceCollection as FastResourceCollection;
use App\Services\V1\Resource\Exceptions\InvalidResponseException;
use Illuminate\Database\Eloquent\Model;

trait ResponseHelper
{
    /**
     * Respond with a collection
     * @param $request
     * @param $data
     * @param int $status
     * @param null $customResource
     * @param array $headers
     * @param array $visible
     * @return \Illuminate\Http\Response
     */
    public function respondCollection(
        $request,
        $data,
        $status = 200,
        $customResource = null,
        $headers = [],
        $visible = []
    ) {
        if ($data instanceof LengthAwarePaginator) {
            $queryParams = $request->except('page');
            $data->appends($queryParams);
        }

        $resource = new FastResourceCollection($data);
        return $this->formatResponseItem($request, $resource, $status, $customResource, $headers, $visible);
    }

    /**
     * Respond with an item
     * @param $request
     * @param $data
     * @param int $status
     * @param null $customResource
     * @param array $headers
     * @param array $visible
     * @return \Illuminate\Http\Response
     */
    public function respondItem($request, $data, $status = 200, $customResource = null, $headers = [], $visible = [])
    {
        $resource = new FastResource($data);
        return $this->formatResponseItem($request, $resource, $status, $customResource, $headers, $visible);
    }

    /**
     * Response wrapper for save, returns location URI in meta.
     * @param        $request
     * @param        $resource
     * @param int    $status
     * @param null   $customResource
     * @param array  $headers
     * @param array  $visible
     * @param string $route
     * @return \Illuminate\Http\Response
     *@throws InvalidResponseException
     * @throws InvalidResourceException
     * @throws InvalidRouteException
     */
    public function respondSave(
        $request,
        $resource,
        $status = 200,
        $customResource = null,
        $headers = [],
        $visible = [],
        $route = null
    ) {
        if (($resource instanceof Model) === false && ($resource instanceof FastResource) === false) {
            throw new InvalidResponseException('Invalid resource');
        }

        if (($resource instanceof Model) === true) {
            $resource = new FastResource($resource);
        }

        if (!is_null($customResource)) {
            $resource->setCustomResource($customResource);
        }

        $data = $resource->createSaveResponse($request, $visible, $route);

        return new Response($data, $status, $headers);
    }

    /**
     * Respond with an empty response and a 204 status
     * @param int $status
     * @param array $headers
     * @return Response
     */
    public function respondEmpty($status = 204, $headers = [])
    {
        return new Response("", $status, $headers);
    }

    /**
     * Format the response data
     * @param $request
     * @param $resource FastResourceCollection | FastResource
     * @param int $status
     * @param null $customResource
     * @param array $headers
     * @param array $visible
     * @return \Illuminate\Http\Response
     *@throws InvalidResponseException
     */
    private function formatResponseItem(
        $request,
        $resource,
        $status = 200,
        $customResource = null,
        $headers = [],
        $visible = []
    ) {
        if (!$resource instanceof ResourceCollection
            && !$resource instanceof LengthAwarePaginator
            && !$resource instanceof Resource
        ) {
            throw new InvalidResponseException("Invalid resource");
        }

        if (!is_null($customResource)) {
            $resource->setCustomResource($customResource);
        }

        $data = $resource->toArray($request, $visible);

        return new Response($data, $status, $headers);
    }
}
