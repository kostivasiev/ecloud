<?php

namespace App\Http\Requests\V2;

use App\Models\V2\Vpc;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsResourceAvailable;
use UKFast\FormRequests\FormRequest;

/**
 * Class CreateLoadBalancerClusterRequest
 * @package App\Http\Requests\V2
 */
class CreateLoadBalancerClusterRequest extends FormRequest
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
     * Get the val
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'nullable|string',
            'availability_zone_id' => 'sometimes|required|string|exists:ecloud.availability_zones,id,deleted_at,NULL',
            'vpc_id' => [
                'required',
                'string',
                'exists:ecloud.vpcs,id,deleted_at,NULL',
                new ExistsForUser(Vpc::class),
                new IsResourceAvailable(Vpc::class),
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
            'availability_zone_id.exists' => 'The specified :attribute was not found',
            'vpc_id.exists' => 'The specified :attribute was not found'
        ];
    }
}
