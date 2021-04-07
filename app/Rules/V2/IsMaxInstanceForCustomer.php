<?php
namespace App\Rules\V2;

use App\Models\V2\Instance;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class IsMaxInstanceForCustomer implements Rule
{
    public function passes($attribute, $value)
    {
        return Instance::forUser(Auth::user())->count() < config('instance.max_limit.total');
    }

    public function message()
    {
        return 'The maximum number of ' . config('instance.max_limit.total') . ' Instances per Customer have been reached';
    }
}
