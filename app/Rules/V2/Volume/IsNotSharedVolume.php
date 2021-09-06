<?php
namespace App\Rules\V2\Volume;

use App\Models\V2\Volume;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class IsNotSharedVolume extends \App\Rules\V2\Instance\IsNotSharedVolume
{
    protected Volume $volume;

    public function __construct($volumeId)
    {
        $this->volume = Volume::forUser(Auth::user())->findOrFail($volumeId);
    }

    public function passes($attribute, $value)
    {
        return !$this->volume->is_shared;
    }
}
