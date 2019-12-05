<?php

namespace App\Http\Controllers\V1\Appliance\Version;

use App\Http\Controllers\Controller;
use App\Models\V1\Appliance\Version\Data;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DataController extends Controller
{
    const ERROR_INVALID_VALUE = 'Invalid value provided';
    const ERROR_DUPLICATE_KEY = 'Duplicate key provided';

    /**
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        return response()->json([
            'data' => Data::select('key', 'value')->where([
                ['appliance_version_uuid', '=', $request->appliance_version_uuid],
            ])->get()->all(),
            'meta' => [],
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function show(Request $request)
    {
        return response()->json([
            'data' => [
                'value' => Data::select('value')->where([
                    ['key', '=', urldecode($request->key)],
                    ['appliance_version_uuid', '=', $request->appliance_version_uuid],
                ])->firstOrFail()->value,
            ],
            'meta' => [],
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function create(Request $request)
    {
        if (empty($request->value)) {
            abort(Response::HTTP_BAD_REQUEST, self::ERROR_INVALID_VALUE);
        }

        if (Data::where([
            ['key', '=', $request->key],
            ['appliance_version_uuid', '=', $request->appliance_version_uuid],
        ])->exists()) {
            abort(Response::HTTP_CONFLICT, self::ERROR_DUPLICATE_KEY);
        }

        $data = Data::create([
            'key' => $request->key,
            'value' => $request->value,
            'appliance_version_uuid' => $request->appliance_version_uuid,
        ]);

        return response()->json([
            'data' => [
                'key' => $data->key,
                'value' => $data->value,
            ],
            'meta' => [
                'location' => config('app.url') . '/v1/appliance-versions/' .
                    $request->appliance_version_uuid . '/data/' . urlencode($data->key)
            ],
        ]);
    }

    /**
     * @param Request $request
     */
    public function delete(Request $request)
    {
        Data::where([
            ['key', '=', urldecode($request->key)],
            ['appliance_version_uuid', '=', $request->appliance_version_uuid],
        ])->firstOrFail()->delete();
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function update(Request $request)
    {
        if (empty($request->value)) {
            abort(Response::HTTP_BAD_REQUEST, self::ERROR_INVALID_VALUE);
        }

        $data = Data::updateOrCreate([
            'key' => urldecode($request->key),
            'appliance_version_uuid' => $request->appliance_version_uuid
        ], [
            'value' => $request->value
        ]);

        return response()->json([
            'data' => [
                'value' => $data->value,
            ],
            'meta' => [],
        ]);
    }
}
