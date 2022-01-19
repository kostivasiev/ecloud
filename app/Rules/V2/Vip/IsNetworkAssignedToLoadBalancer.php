<?php

namespace App\Rules\V2\Vip;

use App\Models\V2\LoadBalancer;
use Illuminate\Contracts\Validation\Rule;

class IsNetworkAssignedToLoadBalancer implements Rule
{
    public LoadBalancer $loadBalancer;

    public function __construct(string $loadBalancerId)
    {
        $this->loadBalancer = LoadBalancer::find($loadBalancerId);
    }

    public function passes($attribute, $value)
    {
        if (!$this->loadBalancer) {
            return false;
        }

       return  $this->loadBalancer->loadBalancerNetworks()->where('network_id', $value)->count() > 0;
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The specified :attribute is not associated with the load balancer.';
    }
}
