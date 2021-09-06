<?php

namespace App\Rules\V2\Volume;

use App\Models\V2\Volume;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class IsMemberOfVolumeGroup implements Rule
{
    public Volume $volume;

    public function __construct($volumeId)
    {
        $this->volume = Volume::forUser(Auth::user())->findOrFail($volumeId);
    }

    public function passes($attribute, $value)
    {
        return empty($this->volume->volume_group_id);
    }

    public function message()
    {
        return 'The volume is already a member of a volume group';
    }
}
