<?php

namespace App\Http\Requests\V2\Instance;

use App\Models\V2\Volume;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\Instance\DetachedIsNotShared;
use App\Rules\V2\VolumeAttachedToInstance;
use App\Rules\V2\VolumeNotOSVolume;
use Illuminate\Foundation\Http\FormRequest;

class VolumeDetachRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $instanceId = app('request')->route('instanceId');
        return [
            'volume_id' => [
                'required',
                'string',
                'exists:ecloud.volumes,id,deleted_at,NULL',
                new ExistsForUser(Volume::class),
                new VolumeAttachedToInstance($instanceId),
                new VolumeNotOSVolume(),
                new DetachedIsNotShared,
            ]
        ];
    }
}
