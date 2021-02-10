<?php

namespace App\Rules\V2;

use App\Models\V2\Network;
use Illuminate\Contracts\Validation\Rule;

class NetworkHasNoAcl implements Rule
{

    public function passes($attribute, $value)
    {
        $network = Network::forUser(app('request')->user)->findOrFail($value);
        return (is_null($network->aclPolicy));
    }

    public function message()
    {
        return 'This :attribute already has an assigned ACL';
    }
}