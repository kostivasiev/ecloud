<?php

namespace App\Rules\V2\Volume;

use App\Models\V2\Volume;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class IsOperatingSystemVolume implements Rule
{
    public Volume $volume;

    public function __construct($volumeId)
    {
        $this->volume = Volume::forUser(Auth::user())->findOrFail($volumeId);
    }

    public function passes($attribute, $value)
    {
        return !$this->volume->os_volume;
    }

    public function message()
    {
        return 'Operating System volumes can not be used as shared volumes';
    }
}
