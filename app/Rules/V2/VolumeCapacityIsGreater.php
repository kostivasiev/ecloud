<?php

namespace App\Rules\V2;

use App\Models\V2\Volume;
use Illuminate\Contracts\Validation\Rule;

class VolumeCapacityIsGreater implements Rule
{

    public Volume $volume;

    public function __construct()
    {
        $volumeId = app('request')->route('volumeId');
        $this->volume = Volume::forUser(app('request')->user())
            ->findOrFail($volumeId);
    }

    public function passes($attribute, $value)
    {
        return ($value > $this->volume->capacity);
    }

    public function message()
    {
        return 'The :attribute must be greater than the current :attribute';
    }
}
