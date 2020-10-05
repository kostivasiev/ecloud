<?php

namespace App\Events\V2;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Router;
use Illuminate\Queue\SerializesModels;

class RouterAvailabilityZoneAttach
{
    use SerializesModels;

    /**
     * @var Router
     */
    public $router;

    /**
     * @var AvailabilityZone
     */
    public $availabilityZone;

    /**
     * @param Router $router
     * @param AvailabilityZone $availabilityZone
     * @return void
     */
    public function __construct(Router $router, AvailabilityZone $availabilityZone)
    {
        $this->router = $router;
        $this->availabilityZone = $availabilityZone;
    }
}
