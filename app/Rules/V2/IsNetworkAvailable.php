<?php
namespace App\Rules\V2;

use App\Models\V2\Network;
use App\Support\Sync;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class IsNetworkAvailable implements Rule
{
    public function passes($attribute, $value)
    {
        $network = Network::forUser(Auth::user())->findOrFail($value);
        return $network->sync->status !== Sync::STATUS_FAILED;
    }

    public function message()
    {
        return 'The specified network is currently in a failed state and cannot be used';
    }
}
