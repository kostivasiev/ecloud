<?php

namespace App\Rules\V2;

use App\Models\V2\Network;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class NetworkHasNoPolicy implements Rule
{

    public function passes($attribute, $value)
    {
        $network = Network::forUser(Auth::user())->findOrFail($value);
        return (is_null($network->networkPolicy));
    }

    public function message()
    {
        return 'This :attribute already has an assigned Policy';
    }
}
