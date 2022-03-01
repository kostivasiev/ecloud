<?php
namespace App\Rules\V2\Volume;

use App\Models\V2\Volume;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class DetachedIsNotShared extends IsNotSharedVolume
{
    public function message()
    {
        return 'Shared volumes cannot be independently detached from instances';
    }
}
