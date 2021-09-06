<?php

namespace App\Http\Requests\V2\Instance;

use App\Models\V2\Volume;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\Instance\IsNotSharedVolume;
use App\Rules\V2\IsSameAvailabilityZone;
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
        $instanceId = $this->route()[2]['instanceId'];
        return [
            'volume_id' => [
                'required',
                'string',
                'exists:ecloud.volumes,id,deleted_at,NULL',
                new ExistsForUser(Volume::class),
                new VolumeNotAttachedToInstance($instanceId),
                new IsSameAvailabilityZone(app('request')->route('instanceId')),
                new IsNotSharedVolume,
            ]
        ];
    }
}
