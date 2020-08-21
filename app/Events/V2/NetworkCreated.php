<?php

namespace App\Events\V2;

use Illuminate\Queue\SerializesModels;
use App\Models\V2\Network;

class NetworkCreated
{
    use SerializesModels;

    public Network $network;

    /**
     * @param Network $network
     * @return void
     */
    public function __construct(Network $network)
    {
        $this->network = $network;
    }
}
