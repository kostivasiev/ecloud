<?php

namespace App\Http\Requests\V2\Vpc;

use App\Models\V2\AvailabilityZone;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsSameRegion;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class DefaultsRequest
 * @package App\Http\Requests\V2\Vpc
 */
class DeployDefaultsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $vpcId = app('request')->route('vpcId');
        return [
            'availability_zone_id' => [
                'required',
                'string',
                'exists:ecloud.availability_zones,id,deleted_at,NULL',
                new ExistsForUser(AvailabilityZone::class),
                new IsSameRegion($vpcId),
            ],
        ];
    }
}
