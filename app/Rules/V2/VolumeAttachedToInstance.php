<?php

namespace App\Rules\V2;

use App\Models\V2\Volume;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class VolumeAttachedToInstance implements Rule
{
    private $instanceId;

    public function __construct($instanceId)
    {
        $this->instanceId = $instanceId;
    }

    public function passes($attribute, $value)
    {
        $volume = Volume::forUser(Auth::user())->findOrFail($value);
        return ($volume->instances()->where('id', '=', $this->instanceId)->count() > 0);
    }

    public function message()
    {
        return 'Action can only be performed on attached volumes';
    }
}
