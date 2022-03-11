<?php

namespace App\Http\Requests\V2\Router;

use App\Models\V2\Router;
use App\Rules\V2\RouterThroughput\ExistsForAvailabilityZone;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $availabilityZoneId = '';
        $router = Router::forUser(Auth::user())->find(Request::route('routerId'));
        if (!empty($router)) {
            $availabilityZoneId = $router->availability_zone_id;
        }

        return [
            'name' => 'sometimes|required|string|max:255',
            'router_throughput_id' => [
                'sometimes',
                'required',
                new ExistsForAvailabilityZone($availabilityZoneId)
            ],
            'is_management' => [
                'sometimes',
                'required',
                'boolean'
            ]
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array|string[]
     */
    public function messages()
    {
        return [
            'name.required' => 'The :attribute field, when specified, cannot be null',
            'availability_zone_id.required' => 'The :attribute field, when specified, cannot be null',
            'availability_zone_id.exists' => 'The specified :attribute was not found',
            'router_throughput_id.required' => 'The :attribute field, when specified, cannot be null',
        ];
    }
}
