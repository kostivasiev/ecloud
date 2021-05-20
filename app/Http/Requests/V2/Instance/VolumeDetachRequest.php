<?php

namespace App\Http\Requests\V2\Instance;

use App\Models\V2\Instance;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\VolumeAttachedToInstance;
use UKFast\FormRequests\FormRequest;

class VolumeDetachRequest extends FormRequest
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
                new ExistsForUser(Instance::class),
                new VolumeAttachedToInstance(),
            ]
        ];
    }
}
