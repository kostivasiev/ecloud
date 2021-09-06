<?php

namespace App\Rules\V2\Instance;

class DetachedIsNotShared extends IsNotSharedVolume
{
    public function message()
    {
        return 'Shared volumes cannot be independently detached from instances';
    }
}
