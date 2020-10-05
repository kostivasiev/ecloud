<?php

namespace App\Events\V2;

use App\Models\V2\Volume;
use Illuminate\Queue\SerializesModels;

class VolumeCapacityUpdate
{
    use SerializesModels;

    /** @var \App\Models\V2\Volume */
    public Volume $volume;

    /**
     * @param Volume $volume
     * @return void
     */
    public function __construct(Volume $volume)
    {
        $this->volume = $volume;
    }
}
