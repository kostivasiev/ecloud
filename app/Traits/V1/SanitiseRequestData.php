<?php

namespace App\Traits\V1;

use Illuminate\Http\Request;

/**
 * Trait SanitiseRequestData
 *
 * This trait can be added to controllers to allow the whitelisting of request data.
 *
 * This will remove any properties that we don't want to update in a model before passing to resource receiveItem()
 *
 * @package App\Traits\V1
 */
trait SanitiseRequestData
{
    /**
     * Remove any properties from the request that we dont want (to update in our model via resource receiveItem())
     * @param Request $request
     * @param $whitelist
     */
    protected function sanitiseRequestData(Request &$request, $whitelist)
    {
        $array = [];
        foreach ($whitelist as $key) {
            // Request params as part of the request
            if ($request->request->has($key)) {
                $array[$key] = $request->request->get($key);
            }
            // Request params as part of the query
            if ($request->query->has($key)) {
                $array[$key] = $request->query->get($key);
            }
        }

        $request->query->replace($array);
        $request->replace($array);
    }
}
