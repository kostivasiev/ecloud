<?php

namespace App\Http\Controllers\V1\Appliance\Version;

use App\Http\Controllers\Controller;
use App\Models\V1\Appliance\Version\Data;
use App\Models\V1\ApplianceVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DataController extends Controller
{
    const ERROR_INVALID_VALUE = 'Invalid "value"';
    const ERROR_CANT_FIND_APPLIANCE_VERSION = 'Can\'t find appliance version';

    /**
     * @param Request $request
     * @return Response
     */
    public function create(Request $request)
    {
        if (empty($request->value)) {
            return new Response(self::ERROR_INVALID_VALUE, Response::HTTP_BAD_REQUEST);
        }

        $applianceVersion = ApplianceVersion::findOrFail($request->appliance_version_uuid);
        if ($request->appliance_version_uuid !== $applianceVersion->appliance_version_uuid) {
            return new Response(self::ERROR_CANT_FIND_APPLIANCE_VERSION, Response::HTTP_NOT_FOUND);
        }

        if ($applianceVersion->active != 'Yes') {
            return new Response(self::ERROR_CANT_FIND_APPLIANCE_VERSION, Response::HTTP_NOT_FOUND);
        }

        if ($applianceVersion->appliance->active != 'Yes') {
            return new Response(self::ERROR_CANT_FIND_APPLIANCE_VERSION, Response::HTTP_NOT_FOUND);
        }

        if ($applianceVersion->appliance->is_public != 'Yes') {
            return new Response(self::ERROR_CANT_FIND_APPLIANCE_VERSION, Response::HTTP_NOT_FOUND);
        }

        return factory(Data::class)->create([
            'key' => $request->key,
            'value' => $request->value,
            'appliance_version_uuid' => $request->appliance_version_uuid,
        ]);
    }
}
