<?php

namespace App\Events\V2\Volume;

use App\Models\V2\Volume;
use Illuminate\Queue\SerializesModels;

class Updated
{
    use SerializesModels;

    public $volume;
    public $originalCapacity;

    /**
     * @param Volume $volume
     * @return void
     */
    public function __construct(Volume $volume)
    {
        $this->volume = $volume;
        $this->originalCapacity = $volume->getOriginal('capacity');
    }
}
