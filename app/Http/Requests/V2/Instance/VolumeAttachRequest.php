<?php

namespace App\Http\Requests\V2\Instance;

use App\Models\V2\Instance;
use App\Models\V2\Volume;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\VolumeNotAttachedToInstance;
use UKFast\FormRequests\FormRequest;

class VolumeAttachRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'volume_id' => [
                'required',
                'string',
                'exists:ecloud.instances,id,deleted_at,NULL',
                new ExistsForUser(Volume::class),
                new VolumeNotAttachedToInstance($this->route('instanceId')),
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
