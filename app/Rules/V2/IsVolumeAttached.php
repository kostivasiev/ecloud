<?php

namespace App\Rules\V2;

use App\Models\V2\Volume;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

/**
 * @deprecated use instance volume
 */
class IsVolumeAttached implements Rule
{

    public Volume $volume;

    public function __construct($id = null)
    {
        $this->volume = Volume::forUser(Auth::user())->findOrFail(
            ($id !== null) ? $id : app('request')->route('volumeId')
        );
    }

    public function passes($attribute, $value)
    {
        return ($this->volume->instances()->count() > 0);
    }

    public function message()
    {
        return 'The IOPS value can only be set on attached volumes';
    }
}
