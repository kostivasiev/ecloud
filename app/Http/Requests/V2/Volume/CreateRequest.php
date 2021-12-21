<?php

namespace App\Http\Requests\V2\Volume;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\VolumeGroup;
use App\Models\V2\Vpc;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsResourceAvailable;
use App\Rules\V2\Volume\HasAvailablePorts;
use Illuminate\Validation\Rule;
use UKFast\FormRequests\FormRequest;

class CreateRequest extends FormRequest
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
            'name' => ['nullable', 'string', 'max:255'],
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
            'capacity' => [
                'required',
                'integer',
                'min:' . config('volume.capacity.min'),
                'max:' . config('volume.capacity.max')
            ],
            'iops' => [
                'sometimes',
                'required',
                'integer',
                'in:300,600,1200,2500',
            ],
            'is_shared' => [
                'sometimes',
                'required',
                'boolean',
            ],
            'volume_group_id' => [
                Rule::exists(VolumeGroup::class, 'id')->whereNull('deleted_at'),
                new ExistsForUser(VolumeGroup::class),
                new HasAvailablePorts
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
            'capacity.min' => 'specified :attribute is below the minimum of ' . config('volume.capacity.min'),
            'capacity.max' => 'specified :attribute is above the maximum of ' . config('volume.capacity.max'),
            'iops.in' => 'The specified :attribute field is not a valid IOPS value (300, 600, 1200, 2500)',
        ];
    }
}
