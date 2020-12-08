<?php

namespace App\Events\V2\Instance;

use App\Events\V2\Instance\Deploy\Data;
use Illuminate\Queue\SerializesModels;

class Deploy
{
    use SerializesModels;

    /**
     * @var Data
     */
    public $data;

    public function __construct(Data $data)
    {
        $this->data = $data;
    }
}
