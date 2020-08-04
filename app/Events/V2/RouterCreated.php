<?php

namespace App\Events\V2;

use Illuminate\Queue\SerializesModels;
use App\Models\V2\Router;

class RouterCreated
{
    use SerializesModels;

    public $router;

    /**
     * @param Router $router
     * @return void
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }
}
