<?php

namespace App\Rules\V2\Volume;

use App\Models\V2\Volume;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class IsNotAttachedToInstance implements Rule
{
    public Volume $volume;

    public function __construct($volumeId)
    {
        $this->volume = Volume::forUser(Auth::user())->findOrFail($volumeId);
    }

    public function passes($attribute, $value)
    {
        return $this->volume->instances()->count() < 1;
    }

    public function message()
    {
        return 'The volume is attached to one or more instances';
    }
}
