<?php

namespace App\Http\Requests\V2\LoadBalancerNetwork;

use App\Models\V2\LoadBalancer;
use App\Models\V2\LoadBalancerNetwork;
use App\Models\V2\Network;
use App\Models\V2\Script;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsResourceAvailable;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class Create extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'required',
                'string'
            ],
            'load_balancer_id' => [
                'required',
                'string',
                new ExistsForUser(LoadBalancer::class),
                new IsResourceAvailable(LoadBalancer::class),
            ],
            'network_id' => [
                'required',
                'string',
                new ExistsForUser(Network::class),
                Rule::unique(LoadBalancerNetwork::class)->where(function ($query) {
                    return $query->where('load_balancer_id', app('request')->input('load_balancer_id'));
                })->whereNull('deleted_at'),
            ],
        ];
    }
}
