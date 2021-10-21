<?php

namespace App\Http\Requests\V2\Vip;

use App\Models\V2\LoadBalancerCluster;
use App\Models\V2\Network;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsResourceAvailable;
use UKFast\FormRequests\FormRequest;

class Update extends FormRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        return [
            'load_balancer_id' => [
                'required',
                'string',
                'exists:ecloud.lbcs,id,deleted_at,NULL',
                new ExistsForUser(LoadBalancerCluster::class),
                new IsResourceAvailable(LoadBalancerCluster::class),
            ],
            'network_id' => [
                'required',
                'string',
                'exists:ecloud.networks,id,deleted_at,NULL',
                new ExistsForUser(Network::class),
                new IsResourceAvailable(Network::class),
            ],
            'allocate_floating_ip' => [
                'numeric'
            ],
        ];
    }
}
