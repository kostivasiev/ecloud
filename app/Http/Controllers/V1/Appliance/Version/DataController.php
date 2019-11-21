<?php

namespace App\Http\Controllers\V1\Appliance\Version;

use App\Http\Controllers\Controller;
use App\Models\V1\Appliance\Version\Data;
use App\Models\V1\ApplianceVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DataController extends Controller
{
    const ERROR_INVALID_VALUE = 'Invalid value provided';
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

        $version = ApplianceVersion::findOrFail($request->appliance_version_uuid);
        if ($request->appliance_version_uuid !== $version->appliance_version_uuid ||
            $version->active != 'Yes' ||
            $version->appliance->active != 'Yes' ||
            $version->appliance->is_public != 'Yes'
        ) {
            return new Response(self::ERROR_CANT_FIND_APPLIANCE_VERSION, Response::HTTP_NOT_FOUND);
        }

        $data = factory(Data::class)->create([
            'key' => $request->key,
            'value' => $request->value,
            'appliance_version_uuid' => $request->appliance_version_uuid,
        ]);

        return new Response(
            json_encode([
                'data' => [
                    'key' => $data->key,
                    'value' => $data->value,
                ],
                'meta' => [
                    'location' => config('app.url') . 'v1/appliance-versions/' . $data->appliance_version_uuid . '/data'
                ],
            ]),
            Response::HTTP_OK
        );
    }
}
