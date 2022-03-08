<?php
namespace App\Services\V1\Resource;

abstract class CustomResource extends Resource
{
    public function filterProperties($request, $data)
    {
        if (!$request->has('properties')) {
            return $data;
        }

        $props = explode(",", $request->input('properties'));
        if (method_exists($this->resource, "persistentProperties")) {
            $persistentProps = $this->resource->persistentProperties();
            if (is_array($persistentProps)) {
                $props = array_merge($persistentProps, $props);
            }
        }
        foreach ($data as $key => $value) {
            if (!in_array($key, $props)) {
                unset($data[$key]);
            }
        }

        return $data;
    }
}
