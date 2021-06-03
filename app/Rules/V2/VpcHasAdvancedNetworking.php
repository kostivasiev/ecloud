<?php
namespace App\Rules\V2;

use App\Models\V2\Network;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class VpcHasAdvancedNetworking implements Rule
{
    public function passes($attribute, $value)
    {
        $network = Network::forUser(Auth::user())->findOrFail($value);
        return $network->router->vpc->advanced_networking;
    }

    public function message()
    {
        return 'Advanced Networking is not enabled for the selected Network resource';
    }
}
