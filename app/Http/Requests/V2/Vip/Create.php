<?php

namespace App\Http\Requests\V2\Vip;

use App\Models\V2\LoadBalancer;
use App\Models\V2\LoadBalancerNetwork;
use App\Models\V2\Network;
use App\Rules\V2\ArePivotResourcesAvailable;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsResourceAvailable;
use Illuminate\Validation\Rule;
use UKFast\FormRequests\FormRequest;

class Create extends FormRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        return [
            'name' => [
                'nullable',
                'string',
                'max:255'
            ],
            'load_balancer_id' => [
                'required',
                'string',
                Rule::exists(LoadBalancerNetwork::class, 'id')->whereNull('deleted_at'),
                new ExistsForUser(LoadBalancerNetwork::class),
                new IsResourceAvailable(LoadBalancerNetwork::class),
                new ArePivotResourcesAvailable(LoadBalancerNetwork::class, ['network', 'loadBalancer']),
            ],
            'allocate_floating_ip' => [
                'sometimes',
                'required',
                'boolean'
            ],
        ];
    }
}
