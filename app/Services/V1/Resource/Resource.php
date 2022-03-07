<?php

namespace App\Services\V1\Resource;

use App\Services\V1\Resource\Traits\ResourceHelper;
use App\Services\V1\Resource\Exceptions\InvalidRouteException;
use App\Services\V1\Resource\Exceptions\InvalidResourceException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource as IlluminateResource;
use Illuminate\Database\Eloquent\Model;

class Resource extends IlluminateResource
{
    use ResourceHelper;

    protected static $currentPathResolver;

    /**
     * A custom resource to use for overriding the automatic responses
     * @var
     */
    protected $customResource;

    /**
     * Transform model to array
     * @param \Illuminate\Http\Request $request
     * @param array $visible
     * @return array
     */
    public function toArray($request, $visible = [])
    {
        if ($this->resource instanceof Model) {
            return [
                'data' => $this->getModelAttributes($this->resource, $request, $visible),
                'meta' => $this->getMeta($this->resource)
            ];
        }

        if (empty($this->customResource) === false) {
            $customResource = new $this->customResource($this->resource);
            return [
                'data' => $customResource->toArray($request, $visible),
                'meta' => $this->getMeta($this->resource)
            ];
        }

        return [
            'data' => $this->resource,
            'meta' => $this->getMeta($this->resource)
        ];
    }
    /**
     * Creates a response with resource id and location URI when resources are saved
     * @param Request $request
     * @param array  $visible
     * @param string $route
     * @throws InvalidResourceException
     * @throws InvalidRouteException
     * @return array
     */
    public function createSaveResponse(Request $request, $visible = [], $route = null)
    {
        if (empty($visible) || is_array($visible) === false) {
            $visible = [$this->getKeyName()];
        }

        $location = $this->getResourceLocation($request, $route);

        return [
            "data" => $this->getModelAttributes($this->resource, $request, $visible),
            "meta" => ['location' => $location]
        ];
    }

    /**
     * Gets resource location URI from the request, or user defined parameter route
     * @param Request     $request
     * @param string|null $route
     * @throws InvalidResourceException
     * @throws InvalidRouteException
     * @return string
     */
    public function getResourceLocation(Request $request, $route = null)
    {
        if (empty($this->getKey())) {
            throw new InvalidResourceException("Invalid Resource");
        }

        // Uses the request URI for updates. For inserts resource id will be added at the end.
        if (empty($route)) {
            $location = static::currentPathResolver()($request);
            $segments = $request->segments();
            $routeLastSegment = end($segments);
            
            if ($request->method() == 'POST' && $routeLastSegment != $this->getKey()) {
                $location .= '/' . $this->getKey();
            }
            
            return $location;
        }

        $route = '/' . trim($route, '/');
        
        // Replaces any route parameters from requests
        foreach ($request->route()[2] as $key => $value) {
            $match = preg_match('/^\w+$/', $key);
            if ($match == false) {
                throw new InvalidRouteException("Invalid route parameter '" . $key . "'");
            }
            $route = preg_replace('/{' . preg_quote($key) . '}/i', $value, $route);
            if ($route === null) {
                throw new InvalidRouteException("Invalid route");
            }
        }

        // Replaces the insert route parameter
        $route = preg_replace('/{\w+}$/i', $this->getKey(), $route);
        if ($route === null) {
            throw new InvalidRouteException("Invalid route");
        }

        $location = $request->root() . $route;
        return $location;
    }

    /**
     * Transform array to model properties
     * @param array $data
     * @param string $modelName
     * @param null|string $friendlyPrimaryKey
     * @return $this
     */
    public function fromArray($data, $modelName, $friendlyPrimaryKey = null)
    {
        if (!is_null($this->customResource)) {
            $customResource = new $this->customResource($this->resource);
            return $customResource->fromArray($data, $modelName, $friendlyPrimaryKey = null);
        }

        $properties = $this->resource->properties();

        // If a friendly primary key name isn't set, see if it is contained in the received data
        if (is_null($friendlyPrimaryKey)) {
            $friendlyPrimaryKey = $this->getFriendlyPrimaryKey($properties);
        }

        // If we have the primary key set, then hydrate the model.
        // Otherwise, this will be treated as a new record
        if (is_null($friendlyPrimaryKey) === false && isset($data[$friendlyPrimaryKey])) {
            $this->resource = $this->resource->find($data[$friendlyPrimaryKey]);
        }

        foreach ($properties as $propertyKey => $property) {
            // Checks if property is not present in data and (not updating model || has no default value set in model)
            if (array_key_exists($property->getFriendlyName(), $data) === false
                && (isset($data[$friendlyPrimaryKey]) === true || is_null($property->getValue()) === true)
            ) {
                continue;
            }

            // Only sets property value if in request data otherwise uses the property default value set in model
            if (isset($data[$property->getFriendlyName()]) === true) {
                $property->setValue($data[$property->getFriendlyName()]);
            }
            
            $this->resource->setAttribute($property->getDatabaseName(), $property->deserialize());
        }

        return $this;
    }

    /**
     * Gets the friendly primary key name for the model
     * @param array $properties
     * @return null|string
     */
    private function getFriendlyPrimaryKey($properties)
    {
        $friendlyPrimaryKey = null;

        foreach ($properties as $propertyKey => $property) {
            if (basename(str_replace('\\', '/', get_class($property))) !== 'IdProperty') {
                continue;
            }

            if ($this->resource->getKeyName() === $property->getDatabaseName()) {
                $friendlyPrimaryKey = $property->getFriendlyName();
                break;
            }
        }

        return $friendlyPrimaryKey;
    }

    public static function setCurrentPathResolver($currentPathResolver)
    {
        static::$currentPathResolver = $currentPathResolver;
    }

    public static function currentPathResolver()
    {
        return static::$currentPathResolver ?: function ($request) {
            return $request->url();
        };
    }
}
