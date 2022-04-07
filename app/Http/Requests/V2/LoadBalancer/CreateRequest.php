<?php

namespace App\Http\Requests\V2\LoadBalancer;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Network;
use App\Models\V2\Vpc;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsResourceAvailable;
use App\Rules\V2\IsSameAvailabilityZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class CreateLoadBalancerClusterRequest
 * @package App\Http\Requests\V2
 */
class CreateRequest extends FormRequest
{
    /**
     * Get the val
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'nullable|string',
            'load_balancer_spec_id' => 'required|string|exists:ecloud.load_balancer_specifications,id',
            'availability_zone_id' => [
                'required',
                'string',
                'exists:ecloud.availability_zones,id,deleted_at,NULL',
                new ExistsForUser(AvailabilityZone::class),
            ],
            'vpc_id' => [
                'required',
                'string',
                'exists:ecloud.vpcs,id,deleted_at,NULL',
                new ExistsForUser(Vpc::class),
                new IsResourceAvailable(Vpc::class),
            ],
            'network_id' => [
                'required',
                'string',
                new ExistsForUser(Network::class),
                new IsResourceAvailable(Network::class),
                new IsSameAvailabilityZone(app('request')->availability_zone_id),
            ],
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
            'load_balancer_spec_id.exists' => 'The specified :attribute was not found',
            'availability_zone_id.exists' => 'The specified :attribute was not found',
            'vpc_id.exists' => 'The specified :attribute was not found'
        ];
    }
}
