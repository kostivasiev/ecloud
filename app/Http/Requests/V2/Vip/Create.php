<?php

namespace App\Http\Requests\V2\Vip;

use App\Models\V2\LoadBalancer;
use App\Models\V2\Network;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsResourceAvailable;
use App\Rules\V2\Vip\IsNetworkAssignedToLoadBalancer;
use Illuminate\Support\Facades\Request;
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
                Rule::exists(LoadBalancer::class, 'id')->whereNull('deleted_at'),
                new ExistsForUser(LoadBalancer::class),
                new IsResourceAvailable(LoadBalancer::class),
            ],
            'network_id' => [
                'required',
                'string',
                Rule::exists(Network::class, 'id')->whereNull('deleted_at'),
                new ExistsForUser(Network::class),
                new IsResourceAvailable(Network::class),
                new IsNetworkAssignedToLoadBalancer(Request::input('load_balancer_id'))
            ],
            'allocate_floating_ip' => [
                'sometimes',
                'required',
                'boolean'
            ],
        ];
    }
}
