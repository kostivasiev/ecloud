<?php

namespace App\Http\Requests\V2;

use App\Models\V2\Vpc;
use App\Rules\V2\ExistsForUser;
use UKFast\FormRequests\FormRequest;

/**
 * Class UpdateLoadBalancerClusterRequest
 * @package App\Http\Requests\V2
 */
class UpdateLoadBalancerClusterRequest extends FormRequest
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
            'name'    => 'nullable|string',
            'availability_zone_id' => 'sometimes|required|string|exists:ecloud.availability_zones,id,deleted_at,NULL',
            'vpc_id' => ['sometimes', 'required', 'string', 'exists:ecloud.vpcs,id,deleted_at,NULL', new ExistsForUser(Vpc::class)]
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
