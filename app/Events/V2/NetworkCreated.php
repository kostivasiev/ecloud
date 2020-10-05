<?php

namespace App\Events\V2;

use App\Models\V2\Network;
use Illuminate\Queue\SerializesModels;

class NetworkCreated
{
    use SerializesModels;

    public $network;

    /**
     * @param Network $network
     * @return void
     */
    public function __construct(Network $network)
    {
        $this->network = $network;
    }
}
