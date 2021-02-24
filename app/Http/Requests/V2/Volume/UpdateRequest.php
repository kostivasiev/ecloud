<?php

namespace App\Http\Requests\V2\Volume;

use App\Models\V2\Vpc;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsVolumeAttached;
use App\Rules\V2\ValidVolumeIops;
use App\Rules\V2\VolumeCapacityIsGreater;
use UKFast\FormRequests\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['sometimes', 'required', 'string'],
            'vpc_id' => [
                'sometimes',
                'required',
                'string',
                'exists:ecloud.vpcs,id,deleted_at,NULL',
                new ExistsForUser(Vpc::class)
            ],
            'availability_zone_id' => [
                'sometimes',
                'required',
                'string',
                'exists:ecloud.availability_zones,id,deleted_at,NULL',
            ],
            'capacity' => [
                'sometimes',
                'required',
                'integer',
                'min:' . config('volume.capacity.min'),
                'max:' . config('volume.capacity.max'),
                new VolumeCapacityIsGreater(),
            ],
            'vmware_uuid' => [
                'sometimes',
                'required',
                'uuid'
            ],
            'iops' => [
                'sometimes',
                'required',
                'numeric',
                'in:300,600,1200,2500'
            ],
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
            'vpc_id.required' => 'The :attribute field, when specified, cannot be null',
            'vpc_id.exists' => 'The specified :attribute was not found',
            'vmware_uuid.required' => 'The :attribute field, when specified, cannot be null',
            'capacity.required' => 'The :attribute field, when specified, cannot be null',
            'capacity.min' => 'specified :attribute is below the minimum of ' . config('volume.capacity.min'),
            'capacity.max' => 'specified :attribute is above the maximum of ' . config('volume.capacity.max'),
            'iops.in' => 'The specified :attribute field is not a valid IOPS value (300, 600, 1200, 2500)',
        ];
    }
}
