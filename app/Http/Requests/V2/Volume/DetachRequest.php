<?php

namespace App\Http\Requests\V2\Volume;

use App\Models\V2\Instance;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsResourceAvailable;
use App\Rules\V2\IsVolumeAttached;
use App\Rules\V2\Volume\DetachedIsNotShared;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @deprecated use instance volume
 */
class DetachRequest extends FormRequest
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
                new IsVolumeAttached(app('request')->route('volumeId')),
                new IsResourceAvailable(Instance::class),
                new DetachedIsNotShared(app('request')->route('volumeId'))
            ]
        ];
    }
}
