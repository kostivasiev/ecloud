<?php

namespace App\Events\V2;

use App\Models\V2\AvailabilityZone;
use Illuminate\Queue\SerializesModels;
use App\Models\V2\Router;

class RouterAvailabilityZoneDetach
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
