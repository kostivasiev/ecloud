<?php
namespace App\Rules\V2;

use App\Models\V2\Network;
use App\Models\V2\Sync;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class IsNetworkNotFailed implements Rule
{
    public function passes($attribute, $value)
    {
        return Network::forUser(Auth::user())->findOrFail($value)->getStatus() !== Sync::STATUS_FAILED;
    }

    public function message()
    {
        return 'The specified network is currently in a failed state and cannot be used';
    }
}
