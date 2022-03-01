<?php

namespace App\Rules\V2;

use App\Models\V2\Vpc;
use Illuminate\Contracts\Validation\Rule;

class VpcHasResources implements Rule
{
    public function passes($attribute, $value)
    {
        $vpc = Vpc::with(['instances', 'routers', 'volumes', 'loadBalancers', 'floatingIps'])->findOrFail($value);

        foreach ($vpc->getRelations() as $relation) {
            if ($relation->count() > 0) {
                return false;
            }
        }

        return true;
    }

    public function message()
    {
        return 'Can not delete VPC with active resources';
    }
}
