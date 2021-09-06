<?php

namespace App\Rules\V2\Volume;

use App\Models\V2\Volume;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class IsSharedVolume implements Rule
{
    public Volume $volume;

    public function __construct($volumeId)
    {
        $this->volume = Volume::forUser(Auth::user())->findOrFail($volumeId);
    }

    public function passes($attribute, $value)
    {
        return $this->volume->is_shared;
    }

    public function message()
    {
        return 'Only shared volumes can be added to a volume group';
    }
}
