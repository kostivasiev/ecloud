<?php

namespace App\Events\V2;

use App\Models\V2\Router;
use Illuminate\Queue\SerializesModels;

class RouterCreated
{
    use SerializesModels;

    /**
     * @var Router
     */
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
