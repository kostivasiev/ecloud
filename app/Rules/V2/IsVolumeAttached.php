<?php

namespace App\Rules\V2;

use App\Models\V2\Volume;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class IsVolumeAttached implements Rule
{

    public Volume $volume;

    public function __construct()
    {
        $volumeId = app('request')->route('volumeId');
        $this->volume = Volume::forUser(Auth::user())
            ->findOrFail($volumeId);
    }

    public function passes($attribute, $value)
    {
        return ($this->volume->instances()->count() > 0);
    }

    public function message()
    {
        return 'The Iops value can only be set on mounted volumes';
    }
}
