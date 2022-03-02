<?php

namespace App\Http\Requests\V2\Volume;

use App\Models\V2\Instance;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsMaxVolumeLimitReached;
use App\Rules\V2\IsResourceAvailable;
use App\Rules\V2\IsSameAvailabilityZone;
use App\Rules\V2\Volume\IsNotSharedVolume;
use App\Rules\V2\VolumeNotAttached;
use UKFast\FormRequests\FormRequest;

/**
 * @deprecated use instance volume
 */
class AttachRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'instance_id' => [
                'required',
                'string',
                'exists:ecloud.instances,id,deleted_at,NULL',
                new ExistsForUser(Instance::class),
                new VolumeNotAttached(app('request')->route('volumeId')),
                new IsMaxVolumeLimitReached(),
                new IsResourceAvailable(Instance::class),
                new IsSameAvailabilityZone(app('request')->route('volumeId')),
                new IsNotSharedVolume(app('request')->route('volumeId')),
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
        ];
    }
}
