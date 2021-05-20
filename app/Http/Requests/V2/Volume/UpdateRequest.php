<?php

namespace App\Http\Requests\V2\Volume;

use App\Models\V2\Vpc;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\VolumeAttachedToInstance;
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
            'capacity.min' => 'specified :attribute is below the minimum of ' . config('volume.capacity.min'),
            'capacity.max' => 'specified :attribute is above the maximum of ' . config('volume.capacity.max'),
            'iops.in' => 'The specified :attribute field is not a valid IOPS value (300, 600, 1200, 2500)',
        ];
    }
}
