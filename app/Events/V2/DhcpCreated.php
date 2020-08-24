<?php

namespace App\Events\V2;

use App\Models\V2\Dhcp;
use Illuminate\Queue\SerializesModels;

class DhcpCreated
{
    use SerializesModels;

    /**
     * @var $dhcp
     */
    public $dhcp;

    /**
     * @param Dhcp $dhcp
     * @return void
     */
    public function __construct(Dhcp $dhcp)
    {
        $this->dhcp = $dhcp;
    }
}
