<?php
namespace App\Rules\V2;

use App\Models\V2\Vpc;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class IsMaxInstanceForVpc implements Rule
{
    public function passes($attribute, $value)
    {
        return Vpc::forUser(Auth::user())->findOrFail($value)->instances()->count() < config('instance.max_limit.per_vpc');
    }

    public function message()
    {
        return 'The maximum number of ' . config('instance.max_limit.per_vpc') . ' Instances per Vpc has been reached';
    }
}
