<?php

namespace App\Http\Requests\V2\Router;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Vpc;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsResourceAvailable;
use App\Rules\V2\RouterThroughput\ExistsForAvailabilityZone;
use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'nullable|string|max:255',
            'vpc_id' => [
                'required',
                'string',
                'exists:ecloud.vpcs,id,deleted_at,NULL',
                new ExistsForUser(Vpc::class),
                new IsResourceAvailable(Vpc::class),
            ],
            'availability_zone_id' => [
                'required',
                'string',
                'exists:ecloud.availability_zones,id,deleted_at,NULL',
                new ExistsForUser(AvailabilityZone::class),
            ],
            'router_throughput_id' => [
                'sometimes',
                'required',
                'exists:ecloud.router_throughputs,id,deleted_at,NULL',
                new ExistsForAvailabilityZone($this->request->get('availability_zone_id'))
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

            'vpc_id.required' => 'The :attribute field is required',
            'vpc_id.exists' => 'The specified :attribute was not found',
        ];
    }
}
