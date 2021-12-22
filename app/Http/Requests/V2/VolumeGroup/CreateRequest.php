<?php
namespace App\Http\Requests\V2\VolumeGroup;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Vpc;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsResourceAvailable;
use UKFast\FormRequests\FormRequest;

class CreateRequest extends FormRequest
{
    protected function rules()
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
        ];
    }
}
