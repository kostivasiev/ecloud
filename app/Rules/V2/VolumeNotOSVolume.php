<?php
namespace App\Rules\V2;

use App\Exceptions\V2\DetachException;
use App\Models\V2\Instance;
use App\Models\V2\Volume;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class VolumeNotOSVolume implements Rule
{
    public function passes($attribute, $value)
    {
        $volume = Volume::forUser(Auth::user())->findOrFail($value);

        return $volume->os_volume != true;
    }

    public function message()
    {
        return 'The specified volume is already attached to this instance';
    }
}
