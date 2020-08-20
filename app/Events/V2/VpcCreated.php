<?php

namespace App\Events\V2;

use App\Models\V2\Vpc;
use Illuminate\Queue\SerializesModels;

class VpcCreated
{
    use SerializesModels;

    /**
     * @var $vpc
     */
    public $vpc;

    /**
     * @param Vpc $vpc
     * @return void
     */
    public function __construct(Vpc $vpc)
    {
        $this->vpc = $vpc;
    }
}
